
async function drawFooter() {
    try {
        const footer = await fetchData("/api/static/html/footer.html");
        if (footer) document.getElementById("uit-footer").innerHTML = footer;
    } catch (error) {
        console.error("Error fetching footer:", error);
    }
}