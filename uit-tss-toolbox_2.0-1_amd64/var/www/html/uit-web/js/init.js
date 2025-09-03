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
    localStorage.setItem('authStr', authStr);
    // Update authStr in the database
    if (authStr === undefined || authStr === null || authStr.length === 0 || authStr == "") {
      console.error("authStr is invalid: " + authStr);
      return false;
    }

    const tokenWorker = new Worker('js/auth-webworker.js');
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

async function fetchData(url) {
  try {
    // Get bearerToken from IndexedDB
    const tokenDB = indexedDB.open("uitTokens", 1);
    tokenDB.onsuccess = function(event) {
      const db = event.target.result;
      const tokenTransaction = db.transaction(["uitTokens"], "readwrite");
      const tokenObjectStore = tokenTransaction.objectStore("uitTokens");
      const bearerTokenRequest = tokenObjectStore.get("bearerToken");
      bearerTokenRequest.onsuccess = async function(event) {
        const bearerTokenObj = event.target.result;
        if (bearerTokenObj === undefined || bearerTokenObj.value === null || bearerTokenObj.value.length === 0 || bearerTokenObj.value == "") {
          throw new Error('No bearer token found in IndexedDB');
        }
        const headers = new Headers();
        headers.append('Content-Type', 'application/x-www-form-urlencoded');
        headers.append('credentials', 'include');
        headers.append('Authorization', 'Bearer ' + bearerTokenObj.value);

        const response = await fetch(url, {
          method: 'GET',
          headers: headers,
          body: null
        });

        if (!response.ok) {
          throw new Error(`Error fetching data: ` + url + ` ${response.status}`);
        }

        // No content (OPTIONS request)
        if (response.status === 204) {
          return null;
        }

        if (response.headers === undefined || response.headers === null || !response.headers.get('Content-Type') || !response.headers.get('Content-Type').includes('application/json')) {
          throw new Error('Response is undefined or not JSON');
        }

        const data = await response.json();
        if (Object.keys(data).length === 0 || data === false || data === undefined || data === null || data == "") {
          throw new Error("Response JSON is empty or invalid: " + url);
        }

        db.close();
        return(data);
      };
      bearerTokenRequest.onerror = function(event) {
        throw new Error("Error retrieving bearerToken from IndexedDB: " + event.target.error)
      };
    };
    tokenDB.onerror = function(event) {
      throw new Error('IndexedDB error: ' + event.target.errorCode);
    };
    tokenDB.onblocked = function(event) {
      throw new Error('IndexedDB blocked: ' + event.target.errorCode);
    };
  } catch (error) {
    console.error("Error fetching data: " + error.message + "\n" + url);
    return null;
  }
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
  };
