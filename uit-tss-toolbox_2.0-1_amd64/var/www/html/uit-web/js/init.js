const tokenDB = indexedDB.open("uitTokens", 1);
tokenDB.onupgradeneeded = (event) => {
  const db = event.target.result;
  console.log(`Upgrading to version ${db.version}`);

  const objectStore = db.createObjectStore("uitTokens", {
    keyPath: "tokenType",
  });

  objectStore.createIndex("authStr", "authStr", { unique: true });
  objectStore.createIndex("basicToken", "basicToken", { unique: true });
  objectStore.createIndex("bearerToken", "bearerToken", { unique: true });
};

const tokenWorker = new Worker('js/auth-webworker.js');

function getCsrfCookie(name) {
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) return parts.pop().split(';').shift();
  return '';
}

function deleteCookie(name, path = "/", domain) {
  let cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=${path};`;
  if (domain) cookie += ` domain=${domain};`;
  document.cookie = cookie;
}

async function getKeyFromIndexDB(key = null) {
  return new Promise((resolve, reject) => {
    if (key === undefined || key === null || key.length === 0 || key == "") {
      reject("Key is invalid: " + key);
      return;
    }
    const tokenDBConnection = indexedDB.open("uitTokens", 1);
    tokenDBConnection.onsuccess = function(event) {
      const db1 = event.target.result;
      const tokenTransaction = db1.transaction(["uitTokens"], "readwrite");
      const tokenObjectStore = tokenTransaction.objectStore("uitTokens");
      const tokenRequest = tokenObjectStore.get(key);
      tokenRequest.onsuccess = async function(event) {
        const tokenObj = event.target.result;
        console.log(tokenObj);
        if (tokenObj === undefined || tokenObj === null) {
          reject("No token found for key: " + key);
          return;
        }
        resolve(tokenObj);
      };
      tokenRequest.onerror = function(event) {
        reject("Error retrieving token from IndexedDB: " + event.target.error);
      };
    };
    tokenDBConnection.onerror = function(event) {
      reject("Error opening IndexedDB: " + event.target.error);
    };
  });
}

function escapeHtml(str) {
  if (typeof str !== 'string') {
    return '';
  }

  const escapeCharacter = (match) => {
    switch (match) {
      case '&': return '&amp;';
      case '<': return '&lt;';
      case '>': return '&gt;';
      case '"': return '&quot;';
      case '\'': return '&#039;';
      case '`': return '&#096;';
      default: return match;
    }
  };

  return str.replace(/[&<>"'`]/g, escapeCharacter);
}

async function generateSHA256Hash(text = null) {
  try {
    if (text === undefined || text === null || text.length === 0 || text == "") {
      throw new Error('Cannot hash empty string');
    }
    const encoder = new TextEncoder();
    const buffer = encoder.encode(text);
    const hashBuffer = await crypto.subtle.digest('SHA-256', buffer);
    
    // Convert the ArrayBuffer to a hexadecimal string
    const hashArray = Array.from(new Uint8Array(hashBuffer));
    const hash = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
    
    return hash;
  } catch (error) {
    console.error("Cannot hash string: " + error);
    return null;
  }
}

function getCreds() {
  const loginForm = document.querySelector("#loginForm");

  loginForm.addEventListener("submit", async (event) => {
    event.preventDefault();
    const formData = new FormData(loginForm);
    const formUser = formData.get("username");
    const formPass = formData.get("password");

    const dataToSend = {
      username: formUser,
      password: formPass
    };

    const authStr = await generateSHA256Hash(formUser) + ':' + await generateSHA256Hash(formPass);
    // Update authStr in the database
    if (authStr === undefined || authStr === null || authStr.length === 0 || authStr == "") {
      console.error("authStr is invalid: " + authStr);
      return false;
    }

    const tokenDB = indexedDB.open("uitTokens", 1);
    tokenDB.onupgradeneeded = (event) => {
      const db = event.target.result;
      console.log(`Upgrading to version ${db.version}`);

      const objectStore = db.createObjectStore("uitTokens", {
        keyPath: "tokenType",
      });

      objectStore.createIndex("authStr", "authStr", { unique: true });
      objectStore.createIndex("basicToken", "basicToken", { unique: true });
      objectStore.createIndex("bearerToken", "bearerToken", { unique: true });
    }

    tokenDB.onsuccess = function(event) {
      const db = event.target.result;
      const tokenTransaction = db.transaction(["uitTokens"], "readwrite")
      const tokenObjectStore = tokenTransaction.objectStore("uitTokens");
        tokenObjectStore
          .put({ tokenType: "authStr", value: authStr })
            .onerror = function(event) {
              throw new Error("Error storing authStr in IndexedDB: " + event.target.error)
            }
          ;
        db.close();
        tokenWorker.postMessage({ type: "updateTokenDB", authStr: authStr });
    }
    tokenDB.onerror = function(event) {
      throw new Error('IndexedDB error: ' + event.target.errorCode);
    }
    ;

    await fetch('/login.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(dataToSend)
      // body: loginForm
    });

    window.location.href = "/index.php";
  });
}

async function fetchData(url, fetchOptions = {}) {
  try {
    if (!url || url.trim().length === 0) {
      throw new Error("No URL specified for fetchData");
    }

    // Get bearerToken from IndexedDB
    const bearerToken = await getBearerToken();
    const headers = new Headers();
    headers.append('Content-Type', 'application/x-www-form-urlencoded');
    headers.append('credentials', 'include');
    headers.append('Authorization', 'Bearer ' + bearerToken);
    

    const response = await fetch(url, {
      method: 'GET',
      headers: headers,
      ...(fetchOptions.signal ? { signal: fetchOptions.signal } : {})
    });

    // No content (OPTIONS request)
    if (response.status === 204) {
      return null;
    }
    if (!response.ok) {
      throw new Error(`Error fetching data: ${url} ${response.status}`);
    }
    if (!response.headers || !response.headers.get('Content-Type') || !response.headers.get('Content-Type').includes('application/json')) {
      throw new Error('Response is undefined or not JSON');
    }
    const data = await response.json();
    if (!data || Object.keys(data).length === 0) {
      console.warn("Response JSON is empty: " + url);
    }
    return data;
  } catch (error) {
    throw error;
  }
}

async function postData(queryType, jsonStr) {
  try {
    if (!queryType || queryType.trim().length === 0 || typeof queryType !== 'string') {
      throw new Error("No queryType specified for postData");
    }
    if (!jsonStr || jsonStr.trim().length === 0) {
      // Check if jsonStr is valid JSON
      try {
        JSON.parse(jsonStr);
      } catch (error) {
        throw new Error("Invalid JSON string specified for postData");
      }
      throw new Error("No JSON string specified for postData");
    }

    // const selection = await window.showOpenFilePicker();
    // if (selection.length > 0) {
    //   const file = await selection[0].getFile();
    //   formData.append("file", file);
    // }

    // Get bearerToken from IndexedDB
    const bearerToken = await getBearerToken();
    const csrfToken = getCsrfCookie('csrf_token');

    
    const headers = new Headers();
    headers.append('Content-Type', 'application/x-www-form-urlencoded');
    headers.append('Authorization', 'Bearer ' + bearerToken);
    if (csrfToken) {
      headers.append('X-CSRF-Token', csrfToken);
    }

    const response = await fetch('https://UIT_WAN_IP_ADDRESS:31411/api/post?type=' + encodeURIComponent(queryType).replace(/'/g, "%27"), {
      method: 'POST',
      headers: headers,
      body: jsonStr,
      credentials: 'include'
    });

    // No content (OPTIONS request)
    if (response.status === 204) {
      return null;
    }
    if (!response.ok) {
      throw new Error(`Error fetching data: ${encodeURIComponent(queryType).replace(/'/g, "%27")} (Status: ${response.status})`);
    }
    if (!response.headers || !response.headers.get('Content-Type') || !response.headers.get('Content-Type').includes('application/json')) {
      throw new Error('Response is undefined or not JSON');
    }

    const data = await response.json();
    if (!data || Object.keys(data).length === 0) {
      console.warn("Response JSON is empty: " + url);
    }
    return data;
  } catch (error) {
    throw error;
  }
}

async function getBearerToken() {
  return new Promise((resolve, reject) => {
    const tokenDBConn = indexedDB.open("uitTokens", 1);
    tokenDBConn.onsuccess = function(event) {
      const db = event.target.result;
      const tokenTransaction = db.transaction(["uitTokens"], "readwrite");
      const tokenObjectStore = tokenTransaction.objectStore("uitTokens");
      const bearerTokenRequest = tokenObjectStore.get("bearerToken");
      bearerTokenRequest.onsuccess = function(event) {
        try {
          const bearerTokenObj = event.target.result;
          if (!bearerTokenObj || !bearerTokenObj.value || typeof bearerTokenObj.value !== "string" || bearerTokenObj.value.trim() === "") {
            db.close();
            reject(new Error('No bearer token found in IndexedDB'));
            return;
          }
          const bearerToken = bearerTokenObj.value;
          resolve(bearerToken);
          return;
        } catch (error) {
          reject(new Error("Error processing bearerToken from IndexedDB: " + error));
          return;
        }
      };
      bearerTokenRequest.onerror = function(event) {
        db.close();
        reject(new Error("Error retrieving bearerToken from IndexedDB: " + event.target.error));
        return;
      };
      tokenDBConn.onerror = function(event) {
        reject(new Error('IndexedDB error: ' + event.target.errorCode));
        return;
      };
      tokenDBConn.onblocked = function(event) {
        reject(new Error('IndexedDB blocked: ' + event.target.errorCode));
        return;
      };
    };
  });
}

async function fetchSSE (type, tag = undefined) {
  return new Promise((resolve, reject) => {

    if (tag === undefined) {
      var sse = new EventSource("/api/sse.php?type=" + type);
    } else {
      var sse = new EventSource("/api/sse.php?type=" + type + "&tagnumber=" + tag);
    }

    sse.addEventListener("server_time", (event) => { 
      if (event.data !== undefined) {
        const ret = JSON.parse(event.data);
        resolve(ret);
      }
    });
    sse.addEventListener("live_image", (event) => { 
      if (event.data !== undefined) {
        const ret = JSON.parse(event.data);
        resolve(ret);
      }
    });
    sse.addEventListener("cpu_temp", (event) => { 
      if (event.data !== undefined) {
        const ret = JSON.parse(event.data);
        resolve(ret);
      }
    });
    sse.addEventListener("disk_temp", (event) => { 
      if (event.data !== undefined) {
        const ret = JSON.parse(event.data);
        resolve(ret);
      }
    });
    sse.onerror = (error) => {
      reject(error);
      sse.close();
    };
  });
}

async function invalidateSession(basicToken) {
  const headers = new Headers();
  headers.append('Content-Type', 'application/x-www-form-urlencoded');
  headers.append('Authorization', 'Basic ' + basicToken);

  try {
    const response = await fetch('https://UIT_WAN_IP_ADDRESS:31411/api/post?type=delete_session', {
      method: 'GET',
      headers: headers,
      credentials: 'include'
    });
    if (!response.ok) {
      console.error("Web server error while checking token: " + response.statusText);
    }

    const data = await response.json();
    if (!data || (typeof data === "object" && Object.keys(data).length === 0)) {
      console.error('Invalidate session: No data returned from API');
    }
    if (data.token && Number(data.ttl) > 0 && (data.valid === true || data.valid === "true")) {
      console.error("Token not invalidated on the server");
    }
  } catch (error) {
    console.error("Error deleting session: " + error);
  }
}

function openImage(imageData) {
  const byteCharacters = atob(imageData);
  const byteNumbers = new Array(byteCharacters.length).fill().map((_, i) => byteCharacters.charCodeAt(i));
  const byteArray = new Uint8Array(byteNumbers);
  const blob = new Blob([byteArray], { type: "image/jpeg" });
  const blobUrl = URL.createObjectURL(blob);
  //window.open(blobUrl);
  window.location.href = blobUrl;
}