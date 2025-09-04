let isRefreshingToken = false;

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
  return new Promise((resolve, reject) => {
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
          if (!authStrObj || !authStrObj.value) {
            reject('authStr is null or empty: ' + authStrObj.value);
            return;
          }
          const authStr = authStrObj.value;

          generateSHA256Hash(authStr).then((basicToken) => {
            if (!basicToken) {
              reject('basicToken hashing failed: ' + basicToken);
              return;
            }

            // Open a new transaction for storing basicToken
            const basicTokenTransaction = db.transaction(["uitTokens"], "readwrite");
            const basicTokenObjectStore = basicTokenTransaction.objectStore("uitTokens");
            const basicTokenPutRequest = basicTokenObjectStore.put({ tokenType: "basicToken", value: basicToken });
            basicTokenPutRequest.onsuccess = function(event) {
              db.close();
            };
            basicTokenPutRequest.onerror = function(event) {
              reject("Error storing basicToken in IndexedDB: " + event.target.error);
              return;
            };
          });
        };
        authStrRequest.onerror = (event) => {
          reject("Error retrieving authStr from IndexedDB: " + event.target.error);
          return;
        };

        // Get bearerToken object
        const bearerTokenRequest = tokenObjectStore.get("bearerToken")
        bearerTokenRequest.onsuccess = function(event) {
          const bearerTokenObj = event.target.result;

          // If token is invalid or expired, request a new one from the API
          if (!bearerTokenObj || !bearerTokenObj.value) {
            console.log("Requesting new token - no token in IndexedDB");
            newToken()
            .then(newBearerToken => {
              if (!newBearerToken) {
                reject('Failed to retrieve new bearerToken');
                return;
              }
              resolve();
              return;
            }).catch(error => {
              reject(error);
              return;
            });
          } else {
            const bearerToken = bearerTokenObj.value;
            checkToken(bearerToken).then(result => {
              if (!result.valid || Number(result.ttl) < 5) {
                console.log("Requesting new token - current token is invalid");
                newToken()
                  .then(newBearerToken => {
                    if (!newBearerToken) {
                      reject('Failed to retrieve new bearerToken');
                      return;
                    }
                    resolve();
                    return;
                  })
                  .catch(error => {
                    reject(error);
                    return;
                  });
            } else {
              resolve();
              return;
            }
            }).catch(error => {
              reject(error);
              return;
            });
          }
        };
        bearerTokenRequest.onerror = function(event) {
          reject("Error fetching bearerToken: " + event.target.error);
          return;
        };
      };
      tokenDB.onerror = function(event) {
        reject("Cannot open token DB: " + event.target.error);
        return;
      };
    } catch (error) {
      reject("Error in checkAndUpdateTokenDB: " + error);
      return;
    }
  });
}


async function checkToken(bearerToken = null) {
  return new Promise((resolve, reject) => {
    if (!bearerToken) {
      reject("No bearerToken provided to checkToken function");
      return;
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

    fetch('https://WAN_IP_ADDRESS:31411/api/auth?type=check-token', requestOptions)
      .then(response => {
        if (!response.ok) {
          console.log("Web server error while checking token: " + response.statusText);
          resolve({ valid: false, ttl: 0 });
          return;
        }
        return response.json();
      })
      .then(data => {
        if (!data || (typeof data === "object" && Object.keys(data).length === 0)) {
          console.log('No data returned from token check API');
          resolve({ valid: false, ttl: 0 });
          return;
        }

        if (data.token && Number(data.ttl) >= 5 && (data.valid === true || data.valid === "true")) {
          resolve({ valid: true, ttl: Number(data.ttl) });
          return;
        } else {
          console.log("Token from API is invalid or TTL is too low");
          resolve({ valid: false, ttl: 0 });
          return;
        }
      })
      .catch(error => {
        console.error("Error checking token validity: " + error);
        resolve({ valid: false, ttl: 0 });
      });
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
        if (!basicTokenObj || !basicTokenObj.value) {
          reject('basicToken object is invalid');
          return;
        }
        const basicToken = basicTokenObj.value;

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
            reject(`Response status: ${response.status}`);
            return;
          }
          return response.json();
        })
        .then(data => {
          if (data.token && (data.valid === true || data.valid === "true")) {
            const newTransaction = db.transaction(["uitTokens"], "readwrite");
            const newObjectStore = newTransaction.objectStore("uitTokens");
            const bearerTokenPutRequest = newObjectStore.put({ tokenType: "bearerToken", value: data.token });
            bearerTokenPutRequest.onsuccess = function() {
              resolve(data.token);
              return;
            };
            bearerTokenPutRequest.onerror = function(event) {
              reject("Error storing new bearerToken in IndexedDB: " + event.target.error);
              return;
            };
          } else {
            reject('No data returned from newToken API');
            return;
          }
        })
        .catch(error => {
          reject("Error fetching new bearerToken: " + error);
          return;
        });
      };
      basicTokenRequest.onerror = function(event) {
        reject("Error fetching basicToken: " + event.target.error);
        return;
      };
    };
    tokenDB.onerror = function(event) {
      reject("Cannot open token DB to create new token: " + event.target.error);
      return;
    };
  });
}

// Wait for a postMessage command from getCreds function
self.addEventListener("message", async (event) => {
  if (event.data.type === "updateTokenDB") {
    checkAndUpdateTokenDB();
  }
});

async function periodicTokenCheck() {
  if (isRefreshingToken) {
    setTimeout(periodicTokenCheck, 1000);
    return;
  }
  isRefreshingToken = true;
  try {
    await checkAndUpdateTokenDB();
  } catch (error) {
    console.error("Error in checkAndUpdateTokenDB:", error);
  }
  // Wait 1 second after checkAndUpdateTokenDB
  isRefreshingToken = false;
  setTimeout(periodicTokenCheck, 1000);
}

// Start the loop
periodicTokenCheck();
