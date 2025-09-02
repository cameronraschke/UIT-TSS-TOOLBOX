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
    }
    tokenDB.onsuccess = function(event) {
      const db = event.target.result;
      const tokenTransaction = db.transaction(["uitTokens"], "readwrite")
      const tokenObjectStore = tokenTransaction.objectStore("uitTokens");

      // Get authStr object
      let authStr = undefined;
      const authStrRequest = tokenObjectStore.get("authStr")
      authStrRequest.onsuccess = (event) => {
        const authStrObj = event.target.result;
        if (authStrObj === undefined || authStrObj === null || authStrObj.length === 0 || authStrObj == "") {
          throw new Error('No authStr found in IndexedDB: ' + authStrObj);
        }
        authStr = authStrObj.value;
        if (authStr === undefined || authStr === null || authStr.length === 0 || authStr == "") {
          throw new Error('authStr is null or empty: ' + authStr);
        }
        tokenObjectStore.put({ tokenType: "authStr", value: authStr })
          .onerror = function(event) {
            throw new Error("Error storing authStr in IndexedDB: " + event.target.error)
          };
      };
      authStrRequest.onerror = (event) => {
        throw new Error("Error retrieving authStr from IndexedDB: " + event.target.error);
      };

      // Hash authStr into basicToken & store basicToken in DB
      let basicToken = undefined;
      const authStrRequest2 = tokenObjectStore.get("authStr")
      authStrRequest2.onsuccess = async function(event) {
        const authStrObj = event.target.result;
        if (authStrObj === undefined || authStrObj === null || authStrObj.length === 0 || authStrObj == "") {
          throw new Error('No authStr found in IndexedDB');
        }
        authStr = authStrObj.value;
    
        basicToken = await generateSHA256Hash(authStr);
        if (basicToken === undefined || basicToken === null || basicToken.length === 0 || basicToken == "") {
          throw new Error('basicToken hashing failed: ' + basicToken);
        }
        tokenObjectStore.put({ tokenType: "basicToken", value: basicToken })
          .onerror = function(event) {
            throw new Error("Error storing basicToken in IndexedDB: " + event.target.error)
          }
        ;
      };
      authStrRequest2.onerror = function(event) {
        throw new Error("Error retrieving authStr from IndexedDB: " + event.target.error);
      };


      // Get bearerToken object
      let bearerTokenObj = undefined;
      const bearerTokenRequest = tokenObjectStore.get("bearerToken")
        bearerTokenRequest.onsuccess = function(event) {
        bearerTokenObj = event.target.result;
        // if (bearerTokenObj === undefined || bearerTokenObj === null || bearerTokenObj.length === 0 || bearerTokenObj == "") {
        //   throw new Error('No bearerToken found in IndexedDB');
        // }
        
        if (checkToken(bearerTokenObj.value) === true) {
            return bearerTokenObj.value;
        } else {
          // If token is invalid or expired, request a new one from the API
          tokenObjectStore.get("basicToken")
            .onsuccess = function(event) {
              const basicTokenObj = event.target.result;
              if (basicTokenObj !== undefined && basicTokenObj.value !== null && basicTokenObj.length > 0 && basicTokenObj != "") {
                newToken(basicTokenObj.value).then((bearerToken) => {
                  tokenObjectStore
                    .put({ tokenType: "bearerToken", value: bearerToken })
                      .onerror = function(event) {
                        throw new Error("Error storing new bearerToken in IndexedDB: " + event.target.error)
                      }
                    ;
                });
              }
            }
          ;
        }
      };

      bearerTokenRequest.onerror = function(event) {
        throw new Error("Error retrieving bearerToken from IndexedDB: " + event.target.error);
      };
      db.close();
    }
  } catch (error) {
    console.error("Error in checkAndUpdateTokenDB: " + error);
    return false;
  }
}


async function checkToken(bearerToken = null) {
  if (bearerToken === undefined || bearerToken === null || bearerToken.length === 0 || bearerToken == "") {
    return false
  }

  try {
    const headers = new Headers({
      'Content-Type': 'application/x-www-form-urlencoded',
      'credentials': 'include',
      'Authorization': 'Bearer ' + bearerToken
    });

    const requestOptions = {
      method: 'GET',
      headers: headers
    };

    const response = await fetch('https://WAN_IP_ADDRESS:31411/api/auth?type=check-token', requestOptions);
    if (!response.ok) {
      return false;
    }

    var data = await response.json();

    // Check if all entries in data are valid
    if (Object.keys(data).length === 0 || data === false || data === undefined || data === null || data == "") {
      return false;
    }

    Object.entries(data).forEach(([key, value]) => {
      if (value["token"] !== undefined && value["token"] !== null && value["token"] != "" && value["ttl"] > 5 && value["valid"] === true) {
        return true;
      } else {
        return false;
      }
    });
    return false;
  } catch (error) {
    console.error(error)
    return false
  }
}


async function newToken(basicToken = null) {
  try {
    const headers = new Headers({
      'Content-Type': 'application/x-www-form-urlencoded',
      'credentials': 'include',
      'Authorization': 'Basic ' + basicToken
    });

    const requestOptions = {
      method: 'GET',
      headers: headers
    };

    const response = await fetch('https://WAN_IP_ADDRESS:31411/api/auth?type=new-token', requestOptions);
    if (!response.ok) {
      throw new Error(`Response status: ${response.status}`);
    }

    var data = await response.json();
    // Check if all entries in data are valid
    if (Object.keys(data).length === 0 || data === false || data === undefined || data === null || data == "") {
      return false;
    }

    Object.entries(data).forEach(([key, value]) => {
      if (value["token"] !== undefined && value["token"] !== null && value["token"] != "" && value["ttl"] > 5 && value["valid"] === true) {
        return value["token"];
      } else {
        return false;
      }
    });

  } catch (error) {
    console.error(error.message);
  }
}

checkAndUpdateTokenDB();
