function jsonToBase64(jsonString) {
    try {
        if (typeof jsonString !== 'string') {
            throw new TypeError("Input is not a valid JSON string");
        }

        const jsonParsed = JSON.parse(jsonString);
        if (!jsonParsed) {
            throw new TypeError("Input is not a valid JSON string");
        }
        if (jsonParsed && typeof jsonParsed === 'object' && Object.prototype.hasOwnProperty.call(jsonParsed, '__proto__')) {
            throw new Error(`Prototype pollution detected`);
        }

        const uft8Bytes = new TextEncoder().encode(jsonString);
        const base64JsonData = uft8Bytes.toBase64({ alphabet: "base64url" })
        // Decode json with base64ToJson and double-check that it's correct.
        const decodedJson = base64ToJson(base64JsonData);
        if (!base64JsonData || JSON.stringify(jsonParsed) !== decodedJson) {
            throw new Error(`Encoded json does not match decoded json. \n${base64JsonData}\n${decodedJson}`)
        }
        return base64JsonData;
    } catch (error) {
        console.error("Invalid JSON string:", error);
        return null;
    }
}

function base64ToJson(base64String) {
    try {
        if (typeof base64String !== 'string') {
            throw new TypeError("Input is not a valid base64 string");
        }
        if (base64String.trim() === "") {
            throw new Error("Base64 string is empty");
        }

        const base64Bytes = atob(base64String);
        const byteArray = new Uint8Array(base64Bytes.length);
        const decodeResult = byteArray.setFromBase64(base64String, { alphabet: "base64url" });
        const jsonString = new TextDecoder().decode(byteArray);
        const jsonParsed = JSON.parse(jsonString);
        if (!jsonParsed) {
            throw new TypeError("Input is not a valid JSON string");
        }
        if (jsonParsed && typeof jsonParsed === 'object' && Object.prototype.hasOwnProperty.call(jsonParsed, '__proto__')) {
            throw new Error(`Prototype pollution detected`);
        }
        return JSON.stringify(jsonParsed);
    } catch (error) {
        console.log("Error decoding base64: " + error);
    }
}