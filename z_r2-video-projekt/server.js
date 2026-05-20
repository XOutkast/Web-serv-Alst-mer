import express from "express";
import path from "path";
import { generateDownloadUrl } from "./upload.js"; // your existing R2 upload helper

const app = express();
const PORT = 3000;

app.use(express.json());

// Serve frontend.html
app.get("/", (req, res) => {
    res.sendFile(path.resolve("frontend.html"));
});

// Endpoint to generate presigned URLs dynamically
app.post("/video-url", async (req, res) => {
    try {
        const { fileKey } = req.body;
        if (!fileKey) return res.status(400).json({ error: "fileKey is required" });

        const url = await generateDownloadUrl(fileKey); // generates R2 presigned URL
        res.json({ url });
    } catch (err) {
        console.error(err);
        res.status(500).json({ error: "Failed to generate presigned URL" });
    }
});

app.listen(PORT, () => console.log(`Server running at http://localhost:${PORT}`));
