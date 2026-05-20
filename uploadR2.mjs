import fs from "fs";
import path from "path";
import {
    S3Client,
    CreateMultipartUploadCommand,
    UploadPartCommand,
    CompleteMultipartUploadCommand,
    AbortMultipartUploadCommand,
    GetObjectCommand,
} from "@aws-sdk/client-s3";
import { getSignedUrl } from "@aws-sdk/s3-request-presigner";

// CONFIG 
const BUCKET_NAME = "muuqaal";
const REGION = "auto";
const ENDPOINT = "https://9f005b2dcbe250dca29326a183101a82.r2.cloudflarestorage.com";
const CHUNK_SIZE = 50 * 1024 * 1024; // 50 MB
const URL_EXPIRATION = 3600; // 1 hour in seconds

// Use environment variables for credentials (recommended)
const client = new S3Client({
    region: REGION,
    endpoint: ENDPOINT,
    credentials: {
        accessKeyId: "47cfe03750f9f2afb547dc5f54b97b15",
        secretAccessKey: "6b33e5ff6a467ca28dcd6bf5dced2b062d5d0bc1599fd10017b076e2673d739c",
    },
});

//  HELPERS 

// Expanded content type mapping
function getContentType(filename) {
    const ext = path.extname(filename).toLowerCase();
    const map = {
        ".mp4": "video/mp4",
        ".m4a": "audio/mp4",
        ".m4b": "audio/mp4",       // audiobook files
        ".mp3": "audio/mpeg",
        ".aac": "audio/aac",
        ".mov": "video/quicktime",
        ".mkv": "video/x-matroska",
        ".avi": "video/x-msvideo",
        ".wmv": "video/x-ms-wmv",
        ".webm": "video/webm",
        ".flv": "video/x-flv",
        ".jpg": "image/jpeg",
        ".jpeg": "image/jpeg",
        ".png": "image/png",
        ".gif": "image/gif",
        ".ogg": "audio/ogg",
    };
    return map[ext] || "application/octet-stream";
}

// Generate presigned download URL
async function generateDownloadUrl(fileKey) {
    const command = new GetObjectCommand({ Bucket: BUCKET_NAME, Key: fileKey });
    return await getSignedUrl(client, command, { expiresIn: URL_EXPIRATION });
}

// Upload a single chunk
async function uploadChunk(uploadId, partNumber, chunk, key) {
    const command = new UploadPartCommand({
        Bucket: BUCKET_NAME,
        Key: key,
        PartNumber: partNumber,
        UploadId: uploadId,
        Body: chunk,
    });
    const response = await client.send(command);
    return { ETag: response.ETag, PartNumber: partNumber };
}

// Complete multipart upload
async function completeUpload(uploadId, parts, key) {
    const command = new CompleteMultipartUploadCommand({
        Bucket: BUCKET_NAME,
        Key: key,
        UploadId: uploadId,
        MultipartUpload: { Parts: parts },
    });
    await client.send(command);
    console.log(`[${key}] Upload complete!`);
}

// Abort multipart upload
async function abortUpload(uploadId, key) {
    const command = new AbortMultipartUploadCommand({
        Bucket: BUCKET_NAME,
        Key: key,
        UploadId: uploadId,
    });
    await client.send(command);
    console.log(`[${key}] Upload aborted.`);
}

// ---------------- MAIN UPLOAD FUNCTION ----------------
async function uploadFile(filePath) {
    const key = path.basename(filePath);
    const contentType = getContentType(filePath);

    const readStream = fs.createReadStream(filePath, { highWaterMark: CHUNK_SIZE });
    let uploadId;
    const parts = [];
    let partNumber = 1;

    try {
        // Start multipart upload with ContentType
        const createCommand = new CreateMultipartUploadCommand({
            Bucket: BUCKET_NAME,
            Key: key,
            ContentType: contentType,
        });
        const createResp = await client.send(createCommand);
        uploadId = createResp.UploadId;
        console.log(`[${key}] Multipart upload started: UploadId=${uploadId}`);

        // Upload chunks
        for await (const chunk of readStream) {
            const part = await uploadChunk(uploadId, partNumber, chunk, key);
            parts.push(part);
            console.log(`[${key}] Uploaded part ${partNumber}`);
            partNumber++;
        }

        // Complete upload
        await completeUpload(uploadId, parts, key);

        // Generate presigned download URL (1 hour, streamable)
        const downloadUrl = await generateDownloadUrl(key);
        console.log(`[${key}] Temporary download URL (1 hour):\n${downloadUrl}`);

    } catch (err) {
        console.error(`[${key}] Upload failed:`, err);
        if (uploadId) await abortUpload(uploadId, key);
    }
}

// ---------------- RUN ----------------
(async () => {
    const files = [
        // "Flaatbed_End_Post_1.mp4",
        "Get Out 2017 1080p.mkv",
    ]; // Add files here

    for (const file of files) {
        await uploadFile(file);
    }
})();
