async function getCreds() {
  const form = document.querySelector("#loginForm"); // Select your form element

  form.addEventListener("submit", (event) => {
    //event.preventDefault();
    const formData = new FormData(form);
    const username = formData.get("username");
    const password = formData.get("password");
    const authStr = username + ':' + password
    localStorage.setItem('authStr', authStr)
  });

    const basicAuth = localStorage.getItem('authStr')
    const encoder = new TextEncoder();
    const data = encoder.encode(basicAuth);
    const hashBuffer = await crypto.subtle.digest('SHA-256', data);
    const hashArray = Array.from(new Uint8Array(hashBuffer));
    const authToken = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');


    localStorage.setItem('basicToken', basicToken)
    localStorage.setItem('authToken', authToken)

}

async function fetchData(url) {
  const basicToken = localStorage.getItem('BasicToken');
  const authToken = localStorage.getItem('AuthToken');
  const headers = new Headers({
    'Content-Type': 'application/json',
    'Authorization': 'Basic ' + basicToken,
    'Authorization': 'Bearer ' + authToken
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