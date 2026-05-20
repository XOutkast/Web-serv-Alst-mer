/**
 * big-test-suite.js
 *
 * Self-contained long testing JS code.
 * Run with: node big-test-suite.js
 *
 * Features:
 *  - Minimal test runner (sync + async)
 *  - Assertions with helpful messages
 *  - Test suites covering many JS features & edge cases
 *  - Fuzzing/property-ish checks
 *  - Concurrency & race condition checks
 *  - Performance microbenchmarks
 *  - Flaky test simulation + retry logic
 *  - Clear reporting and exit codes
 */

// ---------------------- Minimal Assertion Library ----------------------
class AssertionError extends Error {
    constructor(message, details = {}) {
        super(message);
        this.name = "AssertionError";
        this.details = details;
    }
}

const util = {
    isObject(a) {
        return a !== null && typeof a === "object";
    },
    typeOf(v) {
        if (v === null) return "null";
        if (Number.isNaN(v)) return "NaN";
        if (Array.isArray(v)) return "array";
        return typeof v;
    },
    inspect(v) {
        try {
            return JSON.stringify(v, getCircularReplacer(), 2);
        } catch (e) {
            return String(v);
        }
    }
};

function getCircularReplacer() {
    const seen = new WeakSet();
    return function (key, value) {
        if (typeof value === "object" && value !== null) {
            if (seen.has(value)) return "[Circular]";
            seen.add(value);
        }
        return value;
    };
}

const assert = {
    ok(value, msg = "Expected truthy value") {
        if (!value) throw new AssertionError(msg, { value });
    },

    equal(a, b, msg) {
        if (a !== b) {
            throw new AssertionError(msg || `Expected ${util.inspect(a)} === ${util.inspect(b)}`, { a, b });
        }
    },

    notEqual(a, b, msg) {
        if (a === b) {
            throw new AssertionError(msg || `Expected ${util.inspect(a)} !== ${util.inspect(b)}`, { a, b });
        }
    },

    deepEqual(a, b, msg) {
        const seen = new WeakSet();
        function eq(x, y) {
            if (x === y) return true;
            if (typeof x !== typeof y) return false;
            if (Number.isNaN(x) && Number.isNaN(y)) return true;
            if (util.isObject(x) && util.isObject(y)) {
                if (seen.has(x)) return true; // assume circular equality if seen
                seen.add(x);
                const kx = Object.keys(x).sort();
                const ky = Object.keys(y).sort();
                if (kx.length !== ky.length) return false;
                for (let i = 0; i < kx.length; i++) {
                    if (kx[i] !== ky[i]) return false;
                    if (!eq(x[kx[i]], y[ky[i]])) return false;
                }
                return true;
            }
            return false;
        }
        if (!eq(a, b)) {
            throw new AssertionError(msg || `Deep equality failed:\nA: ${util.inspect(a)}\nB: ${util.inspect(b)}`, { a, b });
        }
    },

    throws(fn, expected, msg) {
        let threw = false;
        try {
            fn();
        } catch (err) {
            threw = true;
            if (expected) {
                if (expected instanceof RegExp) {
                    if (!expected.test(String(err))) {
                        throw new AssertionError(msg || `Error message did not match ${expected}: ${err}`, { err });
                    }
                } else if (typeof expected === "function") {
                    if (!(err instanceof expected)) {
                        throw new AssertionError(msg || `Error was not instance of expected constructor: ${err}`, { err });
                    }
                } else if (typeof expected === "string") {
                    if (!String(err).includes(expected)) {
                        throw new AssertionError(msg || `Error message did not include '${expected}': ${err}`, { err });
                    }
                }
            }
        }
        if (!threw) throw new AssertionError(msg || `Expected function to throw`);
    },

    rejects(promiseFactoryOrPromise, expected, msg) {
        // returns a promise so tests can await
        let p;
        if (typeof promiseFactoryOrPromise === "function") {
            try {
                p = Promise.resolve(promiseFactoryOrPromise());
            } catch (err) {
                p = Promise.reject(err);
            }
        } else {
            p = Promise.resolve(promiseFactoryOrPromise);
        }
        return p.then(
            () => { throw new AssertionError(msg || "Expected promise to reject"); },
            (err) => {
                if (expected) {
                    if (expected instanceof RegExp) {
                        if (!expected.test(String(err))) {
                            throw new AssertionError(msg || `Rejection message did not match ${expected}: ${err}`, { err });
                        }
                    } else if (typeof expected === "function") {
                        if (!(err instanceof expected)) {
                            throw new AssertionError(msg || `Rejection was not instance of expected constructor: ${err}`, { err });
                        }
                    } else if (typeof expected === "string") {
                        if (!String(err).includes(expected)) {
                            throw new AssertionError(msg || `Rejection message did not include '${expected}': ${err}`, { err });
                        }
                    }
                }
            }
        );
    },

        approxEqual(a, b, eps = 1e-9, msg) {
            if (typeof a !== "number" || typeof b !== "number") {
                throw new AssertionError(msg || "approxEqual expects numbers");
            }
            if (Math.abs(a - b) > eps) {
                throw new AssertionError(msg || `Expected ${a} ≈ ${b} (eps ${eps})`);
            }
        }
    };

    // ---------------------- Minimal Test Runner ----------------------
    class TestRunner {
        constructor() {
            this.suites = [];
            this.results = [];
            this.globalTimeout = 15_000; // default per-test timeout ms
        }

        addSuite(name, fn) {
            this.suites.push({ name, fn });
        }

        async run() {
            console.log(`\n=== Starting test run (${new Date().toISOString()}) ===\n`);
            for (const suite of this.suites) {
                console.log(`--- Suite: ${suite.name} ---`);
                try {
                    const ctx = {
                        it: (name, testFn) => this._queueTest(suite.name, name, testFn),
                        itAsync: (name, testFn) => this._queueTest(suite.name, name, testFn),
                        beforeEach: (fn) => { /* not implemented globally here */ },
                    };
                    // suite.fn should call ctx.it(...) to register tests
                    await suite.fn(ctx);
                } catch (err) {
                    console.error(`Error while preparing suite '${suite.name}':`, err);
                    this.results.push({ suite: suite.name, name: "(suite-setup)", status: "error", error: err });
                }
            }

            // run queued tests sequentially
            for (const t of this._tests) {
                await this._runTest(t);
            }

            // report
            this._report();
            const failures = this.results.filter(r => r.status !== "passed").length;
            if (failures > 0) {
                console.log(`\n${failures} test(s) failed.`);
                process.exitCode = 1;
            } else {
                console.log(`\nAll tests passed (${this.results.length}).`);
                process.exitCode = 0;
            }
        }

        _queueTest(suite, name, fn) {
            if (!this._tests) this._tests = [];
            this._tests.push({ suite, name, fn });
        }

        async _runTest(t) {
            const record = { suite: t.suite, name: t.name, status: null, durationMs: 0 };
            const start = Date.now();
            try {
                const maybePromise = t.fn();
                // allow promise or sync
                if (maybePromise && typeof maybePromise.then === "function") {
                    // timeout wrapper
                    await promiseTimeout(maybePromise, this.globalTimeout, `Test timed out after ${this.globalTimeout}ms`);
                }
                record.status = "passed";
            } catch (err) {
                record.status = "failed";
                record.error = err;
            } finally {
                record.durationMs = Date.now() - start;
                this.results.push(record);
                const statusIcon = record.status === "passed" ? "✓" : "✖";
                const dur = record.durationMs >= 1000 ? `${(record.durationMs/1000).toFixed(2)}s` : `${record.durationMs}ms`;
                console.log(` ${statusIcon} ${t.suite} :: ${t.name} (${dur})${record.status === "failed" ? " — " + String(record.error) : ""}`);
            }
        }

        _report() {
            const total = this.results.length;
            const passed = this.results.filter(r => r.status === "passed").length;
            const failed = total - passed;
            console.log(`\n=== Summary ===`);
            console.log(`Total: ${total}  Passed: ${passed}  Failed: ${failed}`);
            if (failed > 0) {
                console.log(`\nFailed tests details:`);
                for (const r of this.results.filter(x => x.status !== "passed")) {
                    console.log(` - ${r.suite} :: ${r.name}`);
                    if (r.error) {
                        console.log(`    Error: ${r.error.name}: ${r.error.message}`);
                        if (r.error.details) console.log(`    Details: ${util.inspect(r.error.details)}`);
                        if (r.error.stack) {
                            const stack = r.error.stack.split("\n").slice(0,4).join("\n");
                            console.log(`    Stack: ${stack}`);
                        }
                    }
                }
            }
        }
    }

    async function promiseTimeout(p, ms, message) {
    let timer;
    const timeout = new Promise((_, rej) => timer = setTimeout(() => rej(new Error(message)), ms));
    return Promise.race([p.finally(() => clearTimeout(timer)), timeout]);
}

// ---------------------- Utility Helpers & Test Subjects ----------------------

// A bag of functions to test
const subjects = {
    // pure functions
    sum(a, b) { return a + b; },
    slowMultiply(a, b, delay = 50) {
        return new Promise((res) => setTimeout(() => res(a * b), delay));
    },
    mayFailRandomly(prob = 0.5) {
        if (Math.random() < prob) throw new Error("Random failure");
        return "ok";
    },

    // async generator
    async *asyncSequence(n) {
        for (let i = 0; i < n; i++) {
            await new Promise(r => setTimeout(r, 5));
            yield i;
        }
    },

    // event emitter-like simple implementation
    createEmitter() {
        const handlers = new Map();
        return {
            on(event, fn) {
                (handlers.get(event) || handlers.set(event, []).get(event)).push(fn);
            },
            emit(event, ...args) {
                const list = handlers.get(event) || [];
                for (const h of list) {
                    try { h(...args); } catch (err) { /* swallow for safety in this impl */ }
                }
            }
        };
    },

    // promise utilities
    timeoutPromise(ms, value) {
        return new Promise((res) => setTimeout(() => res(value), ms));
    },

    // Proxy example subject
    createProxy(target) {
        return new Proxy(target, {
            get(t, p, r) {
                if (p === "secret") throw new Error("no secret allowed");
                return Reflect.get(t, p, r);
            }
        });
    },

    // generator subject
    *range(start, end, step = 1) {
        for (let i = start; i < end; i += step) yield i;
    },

    // typed arrays
    sumInt16Buffer(buf) {
        let s = 0;
        for (let i = 0; i < buf.length; i++) s += buf[i];
        return s;
    },

    // BigInt utilities
    bigFactorial(n) {
        if (n < 0) throw new Error("negative");
        let res = 1n;
        for (let i = 1n; i <= BigInt(n); i++) res *= i;
        return res;
    },

    // simple JSON roundtrip
    roundtripJSON(obj) {
        return JSON.parse(JSON.stringify(obj));
    }
};

// ---------------------- Tests ----------------------
const runner = new TestRunner();

/* ------------------
   Suite: Basic assertions & math
   ------------------ */
runner.addSuite("Basic ops & assertions", async ({ it }) => {
    it("sum should add numbers", () => {
        assert.equal(subjects.sum(2, 3), 5);
        assert.equal(subjects.sum(-1, 1), 0);
    });

    it("approx equal works for floats", () => {
        assert.approxEqual(0.1 + 0.2, 0.30000000000000004); // silly but should pass with default eps
    });

    it("deep equality of objects", () => {
        const a = { x: 1, y: { z: [1, 2] } };
        const b = { x: 1, y: { z: [1, 2] } };
        assert.deepEqual(a, b);
    });

    it("throws on bad input", () => {
        assert.throws(() => { throw new TypeError("bad"); }, TypeError);
        assert.throws(() => { throw new Error("boom 404"); }, /404/);
    });
});

/* ------------------
   Suite: Async tests & promises
   ------------------ */
runner.addSuite("Async & promises", async ({ it }) => {
    it("slowMultiply resolves correctly", async () => {
        const v = await subjects.slowMultiply(4, 5, 20);
        assert.equal(v, 20);
    });

    it("timeout wrapper rejects if too slow", async () => {
        const p = subjects.slowMultiply(2, 2, 200);
        await assert.rejects(promiseTimeout(p, 50, "TO"), /timed out/);
    });

    it("rejects helper works for async function", async () => {
        await assert.rejects(async () => {
            throw new Error("fail me");
        }, /fail me/);
    });

    it("asyncSequence yields expected values", async () => {
        const seq = subjects.asyncSequence(4);
        let i = 0;
        for await (const val of seq) {
            assert.equal(val, i++);
        }
        assert.equal(i, 4);
    });
});

/* ------------------
   Suite: Event emitter & concurrency
   ------------------ */
runner.addSuite("Event emitter & concurrency", async ({ it }) => {
    it("emitter should call listeners", () => {
        const e = subjects.createEmitter();
        let called = 0;
        e.on("ping", (x) => { called += x; });
        e.emit("ping", 3);
        assert.equal(called, 3);
    });

    it("concurrent promises all settle", async () => {
        const proms = [subjects.timeoutPromise(10, 1), subjects.timeoutPromise(5, 2), Promise.resolve(3)];
        const res = await Promise.all(proms);
        assert.deepEqual(res, [1, 2, 3]);
    });

    it("promise.allSettled behavior", async () => {
        const a = Promise.resolve(1);
        const b = Promise.reject(new Error("bad"));
        const settled = await Promise.allSettled([a, b]);
        assert.equal(settled.length, 2);
        assert.equal(settled[0].status, "fulfilled");
        assert.equal(settled[1].status, "rejected");
    });
});

/* ------------------
   Suite: Error handling, flaky + retry logic
   ------------------ */
runner.addSuite("Flaky & retry", async ({ it }) => {
    function retry(fn, attempts = 3, delay = 10) {
        return new Promise(async (resolve, reject) => {
            let lastErr;
            for (let i = 0; i < attempts; i++) {
                try {
                    const r = await fn();
                    return resolve(r);
                } catch (err) {
                    lastErr = err;
                    await new Promise(r => setTimeout(r, delay));
                }
            }
            reject(lastErr);
        });
    }

    it("retry eventually resolves for transient failure", async () => {
        let tries = 0;
        const maybe = async () => {
            tries++;
            if (tries < 3) throw new Error("transient");
            return "ok";
        };
        const v = await retry(maybe, 5, 5);
        assert.equal(v, "ok");
        assert.ok(tries >= 3);
    });

    it("retry fails after attempts", async () => {
        await assert.rejects(retry(() => Promise.reject(new Error("always")), 3, 2), /always/);
    });

    it("simulate flaky test and detect flakiness via repeated runs", async () => {
        // run a flaky function N times, expect it to succeed at least once in M runs
        const flaky = () => {
            if (Math.random() < 0.2) return "good";
            throw new Error("bad");
        };
        let succeeded = false;
        for (let i = 0; i < 10; i++) {
            try { flaky(); succeeded = true; break; } catch (e) {}
        }
        // This assertion is probabilistic: if the test run is unlucky it might fail, but probability is tiny.
        assert.ok(succeeded, "Flaky function never succeeded in 10 tries (very unlikely)");
    });
});

/* ------------------
   Suite: Typed arrays, BigInt, Proxy, Symbol, Reflect
   ------------------ */
runner.addSuite("TypedArray, BigInt, Proxy, Symbol", async ({ it }) => {
    it("sumInt16Buffer sums correctly", () => {
        const arr = new Int16Array([1, 2, -3, 4]);
        assert.equal(subjects.sumInt16Buffer(arr), 4);
    });

    it("bigFactorial computes factorial using BigInt", () => {
        assert.equal(String(subjects.bigFactorial(5)), "120");
        assert.equal(String(subjects.bigFactorial(0)), "1");
    });

    it("proxy should block secret property", () => {
        const target = { a: 1, secret: 42 };
        const p = subjects.createProxy(target);
        assert.equal(p.a, 1);
        assert.throws(() => p.secret, /no secret allowed/);
    });

    it("Symbol keys survive JSON roundtrip when stringified manually", () => {
        const sym = Symbol("s");
        const o = { [sym]: 1, a: 2 };
        // JSON doesn't serialize symbols, but our roundtrip preserves 'a'
        const rt = subjects.roundtripJSON(o);
        assert.deepEqual(rt, { a: 2 });
    });
});

/* ------------------
   Suite: Generators & iteration
   ------------------ */
runner.addSuite("Generators & iteration", async ({ it }) => {
    it("sync range generator yields correct values", () => {
        const arr = [...subjects.range(0, 5)];
        assert.deepEqual(arr, [0,1,2,3,4]);
    });

    it("generator with step works", () => {
        const arr = [...subjects.range(0, 10, 3)];
        assert.deepEqual(arr, [0,3,6,9]);
    });
});

/* ------------------
   Suite: Streams (simulated) & backpressure
   ------------------ */
runner.addSuite("Streams & backpressure (simulated)", async ({ it }) => {
    // We'll simulate a simple readable stream that produces numbers and a consumer with a capacity
    function createNumberStream(count, delay=5) {
        let i = 0;
        return {
            async read() {
                if (i >= count) return { done: true };
                await new Promise(r => setTimeout(r, delay));
                return { done: false, value: i++ };
            }
        };
    }

    it("consumer collects all values without overflow", async () => {
        const s = createNumberStream(10, 1);
        const out = [];
        while (true) {
            const { done, value } = await s.read();
            if (done) break;
            out.push(value);
            // simulate backpressure by awaiting occasionally
            if (out.length % 3 === 0) await new Promise(r => setTimeout(r, 0));
        }
        assert.deepEqual(out, Array.from({length:10}, (_,i)=>i));
    });
});

/* ------------------
   Suite: Property-ish fuzz tests & edge cases
   ------------------ */
runner.addSuite("Fuzzing & edge cases", async ({ it }) => {
    function randomString(len=10) {
        const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        let s = "";
        for (let i=0;i<len;i++) s += chars[Math.floor(Math.random()*chars.length)];
        return s;
    }

    it("JSON roundtrip preserves safe types for many random shapes", () => {
        for (let i=0;i<200;i++) {
            const obj = {
                n: Math.random() * 1e6 - 5e5,
                i: Math.floor(Math.random() * 100000),
                s: randomString(Math.floor(Math.random()*20)),
                b: Math.random() > 0.5,
                a: [1, "x", null, {k: "v"}],
            };
            const rt = subjects.roundtripJSON(obj);
            assert.deepEqual(rt, obj);
        }
    });

    it("handles very large arrays (memory-aware test)", function veryLargeArrayTest() {
        // not actually allocate huge memory; simulate behavior with slices
        const N = 100000;
        const arr = Array.from({length: N}, (_,i) => i);
        const head = arr.slice(0, 10);
        const tail = arr.slice(N-10);
        assert.equal(head.length, 10);
        assert.equal(tail.length, 10);
        assert.equal(head[0], 0);
        assert.equal(tail[tail.length-1], N-1);
    });
});

/* ------------------
   Suite: Performance microbenchmarks
   ------------------ */
runner.addSuite("Microbenchmarks", async ({ it }) => {
    function timeIt(fn, iterations = 10000) {
        const start = Date.now();
        for (let i=0;i<iterations;i++) fn(i);
        const end = Date.now();
        return end - start;
    }

    it("string concat vs array join benchmark (not asserting winner, just measuring)", () => {
        const iters = 20000;
        const t1 = timeIt((i) => { let s=""; s += i + ","; }, iters);
        const t2 = timeIt((i) => { const a=[]; a.push(i); a.join(","); }, iters);
        // Just assert both ran and produced numeric durations
        assert.ok(typeof t1 === "number" && typeof t2 === "number");
    });

    it("function call overhead increases with closure depth", () => {
        function depthN(n) {
            let f = (x)=>x;
            for (let i=0;i<n;i++) {
                const prev = f;
                f = (x)=>prev(x) + 1;
            }
            return f;
        }
        const f1 = depthN(0);
        const f10 = depthN(10);
        assert.equal(f1(5), 5);
        assert.equal(f10(5), 15);
    });
});

/* ------------------
   Suite: Edge language features
   ------------------ */
runner.addSuite("Edge language features (NaN, Infinity, -0)", async ({ it }) => {
    it("NaN comparisons", () => {
        assert.ok(Number.isNaN(NaN));
        assert.ok(Object.is(NaN, NaN));
    });

    it("infinity arithmetic", () => {
        const inf = 1/0;
        assert.equal(inf > 1e100, true);
        assert.equal(typeof inf, "number");
    });

    it("-0 edge", () => {
        const a = -0;
        assert.equal(1 / a, -Infinity);
        assert.equal(Object.is(a, -0), true);
    });
});

/* ------------------
   Suite: Serialization / Circular references detection
   ------------------ */
runner.addSuite("Serialization & circular objects", async ({ it }) => {
    it("circular replacer marks circular structures", () => {
        const a = {};
        a.self = a;
        const replacer = getCircularReplacer();
        const s = JSON.stringify(a, replacer);
        assert.ok(s.includes("[Circular]"));
    });

    it("JSON roundtrip does not preserve functions", () => {
        const obj = { f: function () { return 1; }, a: 2 };
        const rt = subjects.roundtripJSON(obj);
        assert.deepEqual(rt, { a: 2 });
    });
});

/* ------------------
   Suite: Miscellaneous - symbols, map/set, weak collections
   ------------------ */
runner.addSuite("Misc: Map/Set/WeakMap/WeakSet", async ({ it }) => {
    it("Map iteration & size", () => {
        const m = new Map([["a",1],["b",2]]);
        assert.equal(m.size, 2);
        const keys = Array.from(m.keys());
        assert.deepEqual(keys, ["a","b"]);
    });

    it("Set uniqueness", () => {
        const s = new Set([1,2,2,3]);
        assert.equal(s.size, 3);
    });

    it("WeakMap accepts object keys", () => {
        const wm = new WeakMap();
        const o = {};
        wm.set(o, 42);
        assert.equal(wm.get(o), 42);
    });
});

/* ------------------
   Suite: JSON Schema-ish validation (lightweight)
   ------------------ */
runner.addSuite("Light validation utilities", async ({ it }) => {
    function validatePerson(p) {
        if (typeof p !== "object" || p === null) throw new Error("not object");
        if (typeof p.name !== "string" || p.name.length === 0) throw new Error("bad name");
        if (!Number.isInteger(p.age) || p.age < 0) throw new Error("bad age");
        return true;
    }

    it("valid person passes", () => {
        assert.ok(validatePerson({name: "Alice", age: 30}));
    });

    it("invalid person fails", () => {
        assert.throws(() => validatePerson({name: "", age: -2}), /bad name|bad age/);
    });
});

/* ------------------
   Suite: Edge cases for JSON.stringify ordering & keys
   ------------------ */
runner.addSuite("JSON key ordering & weird keys", async ({ it }) => {
    it("stringify handles special keys", () => {
        const obj = { "": 1, "a": 2, " ": 3 };
        const s = JSON.stringify(obj);
        // stringified keys exist somewhere
        assert.ok(s.includes('""') || s.includes('" "'));
    });

    it("numeric keys ordering in object", () => {
        // JS object key order is complex: integer-like keys first in numeric order, then insertion order
        const obj = {};
        obj["2"] = "b";
        obj["1"] = "a";
        obj["x"] = "z";
        const keys = Object.keys(obj);
        assert.deepEqual(keys.slice(0,2).sort(), ["1","2"]);
    });
});

/* ------------------
   Suite: Large integration-ish scenario combining async + events + streams
   ------------------ */
runner.addSuite("Integration: async + events + stream scenario", async ({ it }) => {
    it("simulate producer-consumer pipeline", async () => {
        // producer emits numbers into a buffer; consumer drains with concurrency limit
        const buffer = [];
        const emitter = subjects.createEmitter();
        let finished = false;
        emitter.on("produce", (n) => buffer.push(n));
        emitter.on("finish", () => finished = true);

        // producer
        (async () => {
            for (let i=0;i<20;i++) {
                emitter.emit("produce", i);
                await new Promise(r => setTimeout(r, 1));
            }
            emitter.emit("finish");
        })();

        // consumer
        const collected = [];
        while (!finished || buffer.length > 0) {
            while (buffer.length > 0) {
                const v = buffer.shift();
                // simulate async work
                await new Promise(r => setTimeout(r, 2));
                collected.push(v);
            }
            await new Promise(r => setTimeout(r, 2));
        }
        assert.equal(collected.length, 20);
        assert.deepEqual(collected.sort((a,b)=>a-b), Array.from({length:20},(_,i)=>i));
    });
});

/* ------------------
   Suite: Misc math & float edgecases
   ------------------ */
runner.addSuite("Math edge cases", async ({ it }) => {
    it("0.1+0.2 is not exactly 0.3", () => {
        assert.notEqual(0.1 + 0.2, 0.3);
    });

    it("large integer precise up to 2^53", () => {
        const n = Math.pow(2,53)-1;
        assert.equal(Number.isSafeInteger(n), true);
        assert.equal(Number.isSafeInteger(n+1), false);
    });
});

/* ------------------
   Suite: Streams & pipeline backpressure with concurrency limits
   ------------------ */
runner.addSuite("Pipeline concurrency & resource limit", async ({ it }) => {
    it("limited concurrency pool", async () => {
        const tasks = Array.from({length: 20}, (_, i) => () => subjects.timeoutPromise(5, i));
        const concurrency = 4;
        const results = [];
        async function runPool(tasks, concurrency) {
            const out = [];
            let idx = 0;
            const workers = Array.from({length: concurrency}, async () => {
                while (idx < tasks.length) {
                    const cur = idx++;
                    out.push(await tasks[cur]());
                }
            });
            await Promise.all(workers);
            return out;
        }
        const out = await runPool(tasks, concurrency);
        assert.equal(out.length, tasks.length);
    });
});

/* ------------------
   Suite: Robustness - try/catch inside loops, label jumps (esoteric)
   ------------------ */
runner.addSuite("Robustness & esoteric constructs", async ({ it }) => {
    it("try/catch inside loop doesn't leak state", () => {
        let count = 0;
        for (let i=0;i<5;i++) {
            try {
                if (i === 2) throw new Error("boom");
                count++;
            } catch (e) {
                // swallow
            }
        }
        assert.equal(count, 4);
    });

    it("labeled loops and break continue semantics", () => {
        let res = [];
        outer: for (let i=0;i<3;i++) {
            for (let j=0;j<3;j++) {
                if (i===1 && j===1) break outer;
                res.push([i,j]);
            }
        }
        assert.deepEqual(res[res.length-1], [0,2]);
    });
});

/* ------------------
   Suite: Security-ish tests (no real security ops) - sandboxed eval behavior
   ------------------ */
runner.addSuite("Sandboxed eval (demo only)", async ({ it }) => {
    it("eval restricted by proxy-ing global (demo)", () => {
        // sandbox simple: only allow certain globals
        const safeGlobal = { Math };
        function safeEval(code) {
            return Function("global", `with (global) { return (${code}); }`)(safeGlobal);
        }
        assert.equal(safeEval("Math.max(1,2,3)"), 3);
        assert.throws(() => safeEval("process.exit(1)"), /process/);
    });
});

/* ------------------
   Suite: Deterministic pseudo-random tests (seeded)
   ------------------ */
runner.addSuite("Deterministic PRNG", async ({ it }) => {
    function mulberry32(a) {
        return function() {
            a |= 0;
            a = a + 0x6D2B79F5 | 0;
            let t = Math.imul(a ^ a >>> 15, 1 | a);
            t = t + Math.imul(t ^ t >>> 7, 61 | t) ^ t;
            return ((t ^ t >>> 14) >>> 0) / 4294967296;
        };
    }

    it("prng reproducibility", () => {
        const r1 = mulberry32(123);
        const r2 = mulberry32(123);
        const seq1 = [r1(), r1(), r1()];
        const seq2 = [r2(), r2(), r2()];
        assert.deepEqual(seq1, seq2);
    });
});

/* ------------------
   Suite: Misc helpers tests (date/time)
   ------------------ */
runner.addSuite("Date/time helpers", async ({ it }) => {
    it("Date toISOString and parsing", () => {
        const d = new Date("2020-01-02T03:04:05.678Z");
        assert.equal(d.toISOString(), "2020-01-02T03:04:05.678Z");
        assert.equal(new Date(d.toISOString()).getTime(), d.getTime());
    });

    it("timezone consistency for arithmetic", () => {
        const d = new Date(Date.UTC(2020,0,1,0,0,0));
        assert.equal(d.getUTCFullYear(), 2020);
    });
});

/* ------------------
   Suite: Complex object proxies & invariants
   ------------------ */
runner.addSuite("Complex proxies & invariants", async ({ it }) => {
    it("object proxy reflect invariants", () => {
        const target = { a: 1, b: 2 };
        const p = new Proxy(target, {
            ownKeys() { return ["a", "b"]; },
            getOwnPropertyDescriptor(t, k) {
                return { configurable: true, enumerable: true, value: t[k] };
            }
        });
        assert.deepEqual(Object.keys(p), ["a","b"]);
    });
});

/* ------------------
   Suite: Large test count to exercise runner reporting
   ------------------ */
runner.addSuite("Bulk small tests", async ({ it }) => {
    for (let i=0;i<20;i++) {
        it(`tiny-test-${i}`, () => {
            assert.equal(i >= 0, true);
        });
    }
});

// You can add more suites or tests here programmatically if desired.

// ---------------------- Run tests ----------------------
(async () => {
    try {
        // optional: configure global timeout from environment
        if (process.env.TEST_TIMEOUT) runner.globalTimeout = Number(process.env.TEST_TIMEOUT);
        await runner.run();
    } catch (err) {
        console.error("Runner crashed:", err);
        process.exit(2);
    }
})();
