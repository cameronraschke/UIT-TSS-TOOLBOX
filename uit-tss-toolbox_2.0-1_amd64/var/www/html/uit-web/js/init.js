async function generateSHA256Hash(text) {
  const encoder = new TextEncoder(); // Encodes the string to a Uint8Array
  const data = encoder.encode(text);
  const hashBuffer = await crypto.subtle.digest('SHA-256', data); // Hashes the data
  
  // Convert the ArrayBuffer to a hexadecimal string
  const hashArray = Array.from(new Uint8Array(hashBuffer));
  const hash = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
  
  return hash;
}

function getCreds() {

  document.cookie = "authCookie=";

  const loginForm = document.querySelector("#loginForm");

  loginForm.addEventListener("submit", async (event) => {
    event.preventDefault();
    const formData = new FormData(loginForm);
    const formUser = formData.get("username");
    const formPass = formData.get("password");

    // const dataToSend = {
    //   username: formUser,
    //   password: formPass
    // };

    var authStr = await generateSHA256Hash(formUser) + ':' + await generateSHA256Hash(formPass);
    localStorage.setItem('authStr', authStr);

    await fetch('/login.php', {
      method: 'POST',
      // headers: {
      //   'Content-Type': 'application/json'
      // },
      // body: JSON.stringify(dataToSend)
      body: loginForm
    });

    await newToken();

    window.location.href = "/index.php";
  });
}


async function checkToken() {
  if (localStorage.getItem('bearerToken') == undefined) {
    return false
  }

  try {
    const bearerToken = localStorage.getItem('bearerToken');

    const headers = new Headers({
      'Content-Type': 'application/x-www-form-urlencoded',
      'Authorization': 'Bearer ' + bearerToken
    });

    const requestOptions = {
      method: 'GET',
      headers: headers
    };

    const response = await fetch('https://WAN_IP_ADDRESS:31411/api/cookie', requestOptions);
    if (!response.ok) {
      return false;
    }

    var data = await response.json();

    if (data != undefined) {
      return true
    } else {
      return false
    }

  } catch (error) {
    return false
  }
}

async function newToken() {
  const basicToken = await generateSHA256Hash(localStorage.getItem('authStr'));

  localStorage.setItem('basicToken', basicToken)
  
  const headers = new Headers({
    'Content-Type': 'application/x-www-form-urlencoded',
    'Authorization': 'Basic ' + basicToken
  });

  const requestOptions = {
    method: 'GET',
    headers: headers
  };

  try {
    const response = await fetch('https://WAN_IP_ADDRESS:31411/api/auth', requestOptions);
    if (!response.ok) {
      throw new Error(`Response status: ${response.status}`);
    }

    var data = await response.json();

    if (data.token != undefined) {
        localStorage.setItem('bearerToken', data.token);
        document.cookie = "authCookie=" + data.token;
        // document.cookie = "authCookie=Yes";
    } else {
        console.error("No token returned");
    }

  } catch (error) {
    console.error(error.message);
  }
}

async function fetchData(url) {
  if (await checkToken() == false) {
    console.log("Token expired")
    await newToken();
  }
  const bearerToken = localStorage.getItem('bearerToken');

  const headers = new Headers({
    'Content-Type': 'application/json',
    'Authorization': 'Bearer ' + bearerToken
  });

  const requestOptions = {
    method: 'GET',
    credentials: 'include',
    headers: headers
  };

  try {
    const response = await fetch(url, requestOptions);
    if (!response.ok) {
      throw new Error(`Response status: ${response.status}`);
    }   

    const data = await response.json();
    return(data);

  } catch (error) {
    console.error(error.message);
  }
};

  
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
  

  async function parseCPUTemp (tagnumber, currentTemp) {
    try {
      const cpuTemps = await fetchSSE("cpu_temp", tagnumber);
      var tagnumber = tagnumber;
      Object.entries(cpuTemps).forEach(([key, value]) => {
        maxTemp = cpuTemps["max_cpu_temp"];
        lowWarning = maxTemp - (maxTemp * 0.10);
        mediumWarning = maxTemp - (maxTemp * 0.05);
  
        var cpuWarning = document.getElementById("presentCPUTemp-" + tagnumber);
  
        if (cpuWarning) {
          if (currentTemp > lowWarning && currentTemp < mediumWarning) {
            cpuWarning.style.backgroundColor = '#ffe6a0';
          } else if (currentTemp > mediumWarning && currentTemp < maxTemp) {
            cpuWarning.style.backgroundColor = '#f5aa50';
          } else if (currentTemp >= maxTemp) {
            cpuWarning.style.backgroundColor = '#f55050';
          }
        }
      });
    } catch (error) {
      console.log(error);
    }
  }

  async function parseDiskTemp (tagnumber, currentTemp) {
    try {
      const diskTemps = await fetchSSE("disk_temp", tagnumber);
      var tagnumber = tagnumber;
      Object.entries(diskTemps).forEach(([key, value]) => {
        maxTemp = diskTemps["max_disk_temp"];
        lowWarning = maxTemp - (maxTemp * 0.10);
        mediumWarning = maxTemp - (maxTemp * 0.05);
  
        var diskWarning = document.getElementById("presentDiskTemp-" + tagnumber);
  
        if (diskWarning) {
          if (currentTemp > lowWarning && currentTemp < mediumWarning) {
            diskWarning.style.backgroundColor = '#ffe6a0';
          } else if (currentTemp > mediumWarning && currentTemp < maxTemp) {
            diskWarning.style.backgroundColor = '#f5aa50';
          } else if (currentTemp >= maxTemp) {
            diskWarning.style.backgroundColor = '#f55050';
          }
        }
      });
    } catch (error) {
      console.log(error);
    }
  }