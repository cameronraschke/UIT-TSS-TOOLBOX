async function drawHeader() {
  try {
    const header = await fetchData("/api/static/html/header.html");
    if (header) document.getElementById("uit-header").innerHTML = header;
  } catch (error) {
    console.error("Error fetching header:", error);
  }
}