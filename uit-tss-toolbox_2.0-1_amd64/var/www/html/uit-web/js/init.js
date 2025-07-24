async function fetchData(url) {
    try {
      const response = await fetch(url);
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