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
      const tokenTransaction = db.transaction(["uitTokens"], "readwrite")
      const tokenObjectStore = tokenTransaction.objectStore("uitTokens");

      // Get authStr object
      const authStrRequest = tokenObjectStore.get("authStr")
      authStrRequest.onsuccess = async (event) => {
        const authStrObj = event.target.result;
        if (authStrObj === undefined || authStrObj === null || authStrObj.length === 0 || authStrObj == "") {
          throw new Error('No authStr found in IndexedDB: ' + authStrObj);
        }
        
        if (authStrObj.value === undefined || authStrObj.value === null || authStrObj.value.length === 0 || authStrObj.value == "") {
          throw new Error('authStr is null or empty: ' + authStr);
        }
        const authStr = authStrObj.value;
      
        const basicToken = await generateSHA256Hash(authStr);
        if (basicToken === undefined || basicToken === null || basicToken.length === 0 || basicToken == "") {
          throw new Error('basicToken hashing failed: ' + basicToken);
        }
        tokenObjectStore.put({ tokenType: "basicToken", value: basicToken })
          .onerror = function(event) {
            throw new Error("Error storing basicToken in IndexedDB: " + event.target.error)
          }
        ;
      };
      authStrRequest.onerror = (event) => {
        throw new Error("Error retrieving authStr from IndexedDB: " + event.target.error);
      };



      // Get bearerToken object
      const bearerTokenRequest = tokenObjectStore.get("bearerToken")
      bearerTokenRequest.onsuccess = async function(event) {
        const bearerTokenObj = event.target.result;

        // If token is invalid or expired, request a new one from the API
        if (bearerTokenObj === undefined || bearerTokenObj === null || bearerTokenObj.length === 0 || bearerTokenObj == "") {
          await newToken();
        } else {
          const bearerToken = bearerTokenObj.value;
          if (await checkToken(bearerToken) === false) {
            const newBearerToken = await newToken();
            if (newBearerToken === undefined || newBearerToken === null || newBearerToken.length === 0 || newBearerToken == "") {
              throw new Error('Failed to retrieve new bearerToken');
            }
          }
          return bearerToken;
        }
      };
      bearerTokenRequest.onerror = async function(event) {
        const newBearerToken = await newToken();
        if (newBearerToken === undefined || newBearerToken === null || newBearerToken.length === 0 || newBearerToken == "") {
          throw new Error('Failed to retrieve new bearerToken');
        }
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
    throw new Error("No bearerToken provided to checkToken function");
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
      throw new Error(`Response status: ${response.status}`);
    }

    var data = await response.json();

    // Check if all entries in data are valid
    if (Object.keys(data).length === 0 || data === false || data === undefined || data === null || data == "") {
      throw new Error('No data returned from token check API');
    }

    Object.entries(data).forEach(async ([key, value]) => {
      if (value["token"] === undefined && value["token"] === null && value["token"] == "" && value["ttl"] < 5 && value["valid"] === false) {
        const newBearerToken = await newToken();
        if (newBearerToken === undefined || newBearerToken === null || newBearerToken.length === 0 || newBearerToken == "") {
          throw new Error('Failed to retrieve new bearerToken');
        }
      }
      return true;
    });
    return false;
  } catch (error) {
    console.error("Error in checkToken function: " + error);
    return false;
  }
}


async function newToken() {
  try {
    // If token is invalid or expired, request a new one from the API
    tokenDB.onsuccess = function(event) {
      const db = event.target.result;
      const tokenTransaction = db.transaction(["uitTokens"], "readwrite")
      const tokenObjectStore = tokenTransaction.objectStore("uitTokens");

      // Get basicToken
      const basicTokenRequest = tokenObjectStore.get("basicToken")
      basicTokenRequest.onsuccess = async function(event) {
        const basicTokenObj = event.target.result;
        if (basicTokenObj === undefined || basicTokenObj === null || basicTokenObj.length === 0 || basicTokenObj == "") {
          throw new Error('basicToken object is invalid');
        }
        const basicToken = basicTokenObj.value;
        if (basicToken === undefined || basicToken === null || basicToken.length === 0 || basicToken == "") {
          throw new Error('basicToken is invalid');
        }

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

        const data = await response.json();
        // Check if all entries in data are valid
        if (data === undefined || data === null || data == "" || Object.keys(data).length === 0 || data === false) {
          throw new Error("No valid data returned from new token API");
        }

        let bearerToken = undefined;
        Object.entries(data).forEach(([key, value]) => {
          if (value["token"] === undefined && value["token"] === null && value["token"] == "" && value["ttl"] > 5 && value["valid"] === false) {
            return false;
          } else {
            bearerToken = value["token"];
          }
        });

        if (bearerToken === undefined || bearerToken === null || bearerToken.length === 0 || bearerToken == "") {
          throw new Error('Failed to generate new bearerToken');
        }
        
        const bearerTokenPutRequest = tokenObjectStore.put({ tokenType: "bearerToken", value: bearerToken })
        bearerTokenPutRequest.onerror = function(event) {
          throw new Error("Error storing new bearerToken in IndexedDB: " + event.target.error)
        };

        return bearerToken;
      }
    };
    tokenDB.onerror = function(event) {
      throw new Error("Cannot open token DB to create new token: " + event.target.error);
    };

  } catch (error) {
    console.error(error.message);
    return null;
  }
}

// Wait for a postMessage command from getCreds function
self.addEventListener("message", async (event) => {
  if (event.data.type === "updateTokenDB") {
    checkAndUpdateTokenDB();
  }
});

checkAndUpdateTokenDB();

