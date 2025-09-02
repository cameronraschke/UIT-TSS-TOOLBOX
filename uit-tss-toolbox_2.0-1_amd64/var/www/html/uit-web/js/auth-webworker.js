async function checkAndUpdateTokenDB(authStr = null) {
  const tokenDB = indexedDB.open("uit-toolbox", 1);
  tokenDB.onsuccess = function(event) {
    const db = event.target.result;
    // Update authStr in the database
    if (authStr === undefined || authStr === null || authStr.length > 0 || authStr != "") {
      return false;
    }

    db
    .transaction(["tokens"], "readwrite")
    .objectStore("tokens")
    .put({ tokenType: "authStr", value: authStr });

    // Start a new transaction and get hash authStr into basicToken
    db
    .transaction(["tokens"], "readwrite")
    .objectStore("tokens")
    .get("authStr")
    .onsuccess = function(event) {
      const token = event.target.result;
      if (token !== undefined && token.length > 0 && token != "") {
        const newAuthStr = token.value;
        generateSHA256Hash(newAuthStr).then((hashedBasicToken) => {
          const basicToken = hashedBasicToken;
          db
          .transaction(["tokens"], "readwrite")
          .objectStore("tokens")
          .put({ tokenType: "basicToken", value: basicToken });
        });
      }
    };

    // Start a new transaction and get bearerToken object
    db
    .transaction(["tokens"], "readwrite")
    .objectStore("tokens")
    .get("bearerToken")
    .onsuccess = function(event) {
      const token = event.target.result;
      if (token !== undefined && checkToken(token.value) === true) {
          return token.value;
      } else {
        // If token is invalid or expired, request a new one from the API
        db
        .transaction(["tokens"], "readwrite")
        .objectStore("tokens")
        .get("basicToken")
        .onsuccess = function(event) {
          const basicTokenObj = event.target.result;
          if (basicTokenObj !== undefined && basicTokenObj.value !== null && basicTokenObj.length > 0 && basicTokenObj != "") {
            newToken(basicTokenObj.value).then((bearerToken) => {
              db
              .transaction(["tokens"], "readwrite")
              .objectStore("tokens")
              .put({ tokenType: "bearerToken", value: bearerToken });
            });
          }
        }
      }
    };
  }
  db.close();
}


async function generateSHA256Hash(text) {
  const encoder = new TextEncoder(); // Encodes the string to a Uint8Array
  const data = encoder.encode(text);
  const hashBuffer = await crypto.subtle.digest('SHA-256', data); // Hashes the data
  
  // Convert the ArrayBuffer to a hexadecimal string
  const hashArray = Array.from(new Uint8Array(hashBuffer));
  const hash = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
  
  return hash;
}


function checkToken(bearerToken = null) {
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

    const response = fetch('https://WAN_IP_ADDRESS:31411/api/auth?type=check-token', requestOptions);
    if (!response.ok) {
      return false;
    }

    var data = response.json();

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

