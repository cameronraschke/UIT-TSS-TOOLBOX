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

async function checkAndUpdateTokenDB() {
  try {
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
    tokenDB.onsuccess = function(event) {
      const db = event.target.result;

      // Get authStr object
      const tokenTransaction = db.transaction(["uitTokens"], "readonly");
      const tokenObjectStore = tokenTransaction.objectStore("uitTokens");
      const authStrRequest = tokenObjectStore.get("authStr");
      authStrRequest.onsuccess = function(event) {
        const authStrObj = event.target.result;
        if (authStrObj === undefined || authStrObj === null || authStrObj.length === 0 || authStrObj == "") {
          throw new Error('No authStr found in IndexedDB: ' + authStrObj);
        }
        if (authStrObj.value === undefined || authStrObj.value === null || authStrObj.value.length === 0 || authStrObj.value == "") {
          throw new Error('authStr is null or empty: ' + authStrObj.value);
        }
        const authStr = authStrObj.value;

        generateSHA256Hash(authStr).then((basicToken) => {
          if (basicToken === undefined || basicToken === null || basicToken.length === 0 || basicToken == "") {
            throw new Error('basicToken hashing failed: ' + basicToken);
          }

          // Open a new transaction for storing basicToken
          const basicTokenTransaction = db.transaction(["uitTokens"], "readwrite");
          const basicTokenObjectStore = basicTokenTransaction.objectStore("uitTokens");
          const basicTokenPutRequest = basicTokenObjectStore.put({ tokenType: "basicToken", value: basicToken });
          basicTokenPutRequest.onsuccess = function(event) {
            db.close();
          };
          basicTokenPutRequest.onerror = function(event) {
            throw new Error("Error storing basicToken in IndexedDB: " + event.target.error);
          };
        });
      };
      authStrRequest.onerror = (event) => {
        throw new Error("Error retrieving authStr from IndexedDB: " + event.target.error);
      };

      // Get bearerToken object
      const bearerTokenRequest = tokenObjectStore.get("bearerToken")
      bearerTokenRequest.onsuccess = function(event) {
        const bearerTokenObj = event.target.result;

        // If token is invalid or expired, request a new one from the API
        if (bearerTokenObj.value === undefined || bearerTokenObj.value === null || bearerTokenObj.value.length === 0 || bearerTokenObj.value == "") {
          newToken().then(newBearerToken => {
            if (newBearerToken === undefined || newBearerToken === null || newBearerToken.length === 0 || newBearerToken == "") {
              throw new Error('1 Failed to retrieve new bearerToken');
            }
          });
        } else {
          const bearerToken = bearerTokenObj.value;
          checkToken(bearerToken).then(isValid => {
            if (isValid === false) {
              newToken().then(newBearerToken => {
                if (
                  newBearerToken === undefined ||
                  newBearerToken === null ||
                  newBearerToken.length === 0 ||
                  newBearerToken == ""
                ) {
                  throw new Error('2 Failed to retrieve new bearerToken');
                }
              });
            }
          });
        }
      };
      bearerTokenRequest.onerror = function(event) {
        newToken().then(newBearerToken => {
          if (newBearerToken === undefined || newBearerToken === null || newBearerToken.length === 0 || newBearerToken == "") {
              throw new Error('3 Failed to retrieve new bearerToken');
            }
          });
        if (newBearerToken === undefined || newBearerToken === null || newBearerToken.length === 0 || newBearerToken == "") {
          throw new Error('4 Failed to retrieve new bearerToken');
        }
      };
      bearerTokenRequest.onerror = function(event) {
        newToken().then(newBearerToken => {
          if (newBearerToken === undefined || newBearerToken === null || newBearerToken.length === 0 || newBearerToken == "") {
              throw new Error('5 Failed to retrieve new bearerToken');
            }
          });
        if (newBearerToken === undefined || newBearerToken === null || newBearerToken.length === 0 || newBearerToken == "") {
          throw new Error('6 Failed to retrieve new bearerToken');
        }
      };
    }
  } catch (error) {
    console.error("Error in checkAndUpdateTokenDB: " + error);
    return false;
  }
}


async function checkToken(bearerToken = null) {
  return new Promise((resolve, reject) => {
    if (bearerToken === undefined || bearerToken === null || bearerToken.length === 0 || bearerToken == "") {
      reject("No bearerToken provided to checkToken function");
      return false;
    }

    const headers = new Headers({
      'Content-Type': 'application/x-www-form-urlencoded',
      'credentials': 'include',
      'Authorization': 'Bearer ' + bearerToken
    });

    const requestOptions = {
      method: 'GET',
      headers: headers
    };

    console.log("Checking token");
    fetch('https://WAN_IP_ADDRESS:31411/api/auth?type=check-token', requestOptions)
      .then(response => {
        if (!response.ok) {
          throw new Error(`Response status: ${response.status}`);
        }
        return response.json();
      })
      .then(data => {
        if (Object.keys(data).length === 0 || data === false || data === undefined || data === null || data == "") {
          reject('No data returned from token check API');
          return false;
        }

        if (
          data.token !== undefined &&
          data.token !== null &&
          data.token !== "" &&
          Number(data.ttl) >= 5 &&
          (data.valid === true || data.valid === "true")
        ) {
          resolve(true);
        } else {
          resolve(false);
        }
      })
      .catch(error => {
        reject("Error in fetch: " + error);
      });

      resolve(true);
      return true;
  });
}


async function newToken() {
  return new Promise((resolve, reject) => {
    // If token is invalid or expired, request a new one from the API
    const tokenDB = indexedDB.open("uitTokens", 1);
    tokenDB.onsuccess = function(event) {
      const db = event.target.result;
      const tokenTransaction = db.transaction(["uitTokens"], "readwrite");
      const tokenObjectStore = tokenTransaction.objectStore("uitTokens");

      // Get basicToken
      const basicTokenRequest = tokenObjectStore.get("basicToken")
      basicTokenRequest.onsuccess = function(event) {
        const basicTokenObj = event.target.result;
        if (basicTokenObj === undefined || basicTokenObj === null || basicTokenObj.length === 0 || basicTokenObj == "") {
          reject('basicToken object is invalid');
          return null;
        }
        if (basicTokenObj.value === undefined || basicTokenObj.value === null || basicTokenObj.value.length === 0 || basicTokenObj.value == "") {
          reject('basicToken value is invalid');
          return null;
        }
        const basicToken = basicTokenObj.value;

        console.log("Requesting new token");
        fetch('https://WAN_IP_ADDRESS:31411/api/auth?type=new-token', {
          method: 'GET',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'credentials': 'include',
            'Authorization': 'Basic ' + basicToken
          },
          body: null
        })
        .then(response => {
          if (!response.ok) {
            throw new Error(`Response status: ${response.status}`);
          }
          return response.json();
        })
        .then(data => {
          // Check if all entries in data are valid
          if (data === undefined || data === null || data == "" || Object.keys(data).length === 0 || data === false) {
            throw new Error("No valid data returned from new token API");
          }
          if (
            data.token !== undefined &&
            data.token !== null &&
            data.token !== "" &&
            Number(data.ttl) >= 5 &&
            (data.valid === true || data.valid === "true")
          ) {
            const newTransaction = db.transaction(["uitTokens"], "readwrite");
            const newObjectStore = newTransaction.objectStore("uitTokens");
            const bearerTokenPutRequest = newObjectStore.put({ tokenType: "bearerToken", value: data.token });
            bearerTokenPutRequest.onsuccess = function() {
              resolve(data.token);
            };
            bearerTokenPutRequest.onerror = function(event) {
              reject("Error storing new bearerToken in IndexedDB: " + event.target.error);
            };
          } else {
            reject("No valid bearer token found");
            return null;
          }
        })
        .catch(error => {
          reject("Error fetching new bearerToken: " + error);
        });
      };
      basicTokenRequest.onerror = function(event) {
        reject("Error fetching basicToken: " + event.target.error);
      };
    };
    tokenDB.onerror = function(event) {
      reject("Cannot open token DB to create new token: " + event.target.error);
    };
  });
}

// Wait for a postMessage command from getCreds function
self.addEventListener("message", async (event) => {
  if (event.data.type === "updateTokenDB") {
    checkAndUpdateTokenDB();
  }
});

// set timeout
setInterval(() => {
  checkAndUpdateTokenDB();
}, 1000);
checkAndUpdateTokenDB();
