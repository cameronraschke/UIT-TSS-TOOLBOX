
if (document.getElementById("uit-footer")) {
    const footer = await fetchData("/api/static/html/footer.html");
    if (footer) document.getElementById("uit-footer").innerHTML = footer;
}