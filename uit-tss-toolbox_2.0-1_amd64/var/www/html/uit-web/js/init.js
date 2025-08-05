function getCreds() {
  const loginForm = document.querySelector("#loginForm");

  loginForm.addEventListener("submit", (event) => {
    event.preventDefault();
    const formData = new FormData(loginForm);
    const username = formData.get("username");
    const password = formData.get("password");
    const authStr = username + ':' + password;
    localStorage.setItem('authStr', authStr);

    fetch('/login.php', {
      method: 'POST',
      body: formData
    });
  });
}

async function getToken() {
  const authStr = localStorage.getItem('authStr')
  const encoder = new TextEncoder();
  const data = encoder.encode(authStr);
  const hashBuffer = await crypto.subtle.digest('SHA-256', data);
  const hashArray = Array.from(new Uint8Array(hashBuffer));
  const basicToken = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');

  localStorage.setItem('basicToken', basicToken)
  
  const headers = new Headers({
    'Content-Type': 'application/x-www-form-urlencoded',
    'Authorization': 'Basic ' + basicToken,
  });

  const requestOptions = {
    method: 'GET',
    headers: headers
  };

  try {
    const response = await fetch(url, requestOptions);
    if (!response.ok) {
      throw new Error(`Response status: ${response.status}`);
    }   

    const data = await response.json();
    jsonData = JSON.parse(data);
    Object.entries(jsonData).forEach(([key, value]) => {
      if (key == 'token') {
        bearerToken = value;
        localStorage.setItem('bearerToken', bearerToken);
      } else {
        console.error("No token returned");
      }
    });

  } catch (error) {
    console.error(error.message);
  }
}

async function fetchData(url) {
  const bearerToken = localStorage.getItem('bearerToken');

  const headers = new Headers({
    'Content-Type': 'application/json',
    'Authorization': 'Bearer ' + bearerToken
  });

  const requestOptions = {
    method: 'GET',
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