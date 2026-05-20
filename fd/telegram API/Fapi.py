"""
telegram_mirror_copy_resume.py
- Mirrors a source channel into a private target channel entirely inside Telegram.
- Uses server-side copying (as_copy=True) so no media is downloaded locally.
- Writes progress to progress.json so runs can be resumed.
- Requirements: pip install telethon
"""

import asyncio
import json
import time
from pathlib import Path
from telethon import TelegramClient, errors
from telethon.tl.types import Message

# ================== CONFIG ==================
API_ID = 1234567                       # <-- REPLACE
API_HASH = "your_api_hash_here"        # <-- REPLACE
SESSION = "mirror_session"             # session file
SOURCE = "source_channel_username_or_id"   # e.g. "my_old_channel" or -1001234567890
TARGET = "my_private_mirror_channel"       # your private channel (you must be admin)
PROGRESS_FILE = Path("progress.json")  # where progress is stored
BATCH_PRINT = 100                       # print progress every N messages
# ============================================

client = TelegramClient(SESSION, API_ID, API_HASH)

def load_progress():
    if PROGRESS_FILE.exists():
        try:
            with open(PROGRESS_FILE, "r", encoding="utf-8") as f:
                return json.load(f)
        except Exception:
            return {"processed_ids": []}
    return {"processed_ids": []}

def save_progress(progress):
    with open(PROGRESS_FILE, "w", encoding="utf-8") as f:
        json.dump(progress, f, ensure_ascii=False)

async def ensure_entity(name_or_id):
    return await client.get_entity(name_or_id)

async def mirror():
    await client.start()
    src = await ensure_entity(SOURCE)
    tgt = await ensure_entity(TARGET)
    print(f"Source: {getattr(src,'title',str(src))}")
    print(f"Target: {getattr(tgt,'title',str(tgt))}")
    progress = load_progress()
    processed = set(progress.get("processed_ids", []))

    count = 0
    skipped = 0
    first = True

    # iterate oldest -> newest
    async for msg in client.iter_messages(src, reverse=False):
        # msg.id is unique per channel
        sid = str(msg.id)
        if sid in processed:
            skipped += 1
            continue

        try:
            # 1) Post an attribution line so we know original author & date
            who = None
            if msg.from_id:
                # user or channel who posted
                if getattr(msg.from_id, "user_id", None):
                    who = f"user_id:{msg.from_id.user_id}"
                elif getattr(msg.from_id, "channel_id", None):
                    who = f"channel_id:{msg.from_id.channel_id}"
                else:
                    # fallback
                    who = str(msg.from_id)
            else:
                # fallback to sender object if present
                if msg.sender:
                    who = getattr(msg.sender, "username", None) or getattr(msg.sender, "title", None) or f"id:{getattr(msg.sender,'id',None)}"
                else:
                    who = "unknown"

            date_str = msg.date.isoformat() if getattr(msg, "date", None) else "unknown_date"
            attrib_text = f"ORIGINAL_POST\nfrom: {who}\nsource_msg_id: {msg.id}\ndate: {date_str}"

            # send the attribution as a plain message (no media)
            await client.send_message(tgt, attrib_text)

            # 2) Copy the message server-side into the target (creates an independent message)
            # as_copy=True -> create new message without forward-linking to original
            await client.forward_messages(tgt, msg, from_peer=src, as_copy=True)

            # mark processed and save periodically
            processed.add(sid)
            count += 1
            if count % BATCH_PRINT == 0:
                progress["processed_ids"] = list(processed)
                save_progress(progress)
                print(f"Processed {count} messages (skipped {skipped})... total tracked {len(processed)}")
        except errors.FloodWaitError as e:
            # Telegram asks us to sleep for e.seconds
            print(f"Flood wait: sleeping {e.seconds} seconds...")
            await asyncio.sleep(e.seconds + 1)
        except Exception as e:
            # log error and continue (so a single bad message doesn't stop everything)
            print(f"Error copying message id {msg.id}: {type(e).__name__}: {e}")
            # optional: keep a small delay to avoid tight failure loops
            await asyncio.sleep(1)

            # final save
            progress["processed_ids"] = list(processed)
            save_progress(progress)
            print(f"Done. Processed {count} new messages; skipped {skipped} already-processed.")

        if name == "__main__":
            try:
                asyncio.run(mirror())
            except KeyboardInterrupt:
                print("Interrupted by user.")