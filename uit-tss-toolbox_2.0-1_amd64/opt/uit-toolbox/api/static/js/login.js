let submitInProgress = false;

const loginForm = document.querySelector("#login-form");
const usernameInput = document.getElementById("username");
const passwordInput = document.getElementById("password");
const loginButton = document.getElementById("login-button");
const usernameStar = document.getElementById("username-star");
const passwordStar = document.getElementById("password-star");

function checkUsernameValidity() {
    const usernameValid = usernameInput.checkValidity();
    console.log("username: " + usernameValid);
    if (!usernameValid) {
        usernameStar.style.display = "block";
        usernameStar.style.color = "red";
    } else {
        usernameStar.style.display = "none";
        usernameStar.style.color = "black";
    }
}

function checkPasswordValidity() {
    const passwordValid = passwordInput.checkValidity();
    console.log("password: " + passwordValid);
    if (!passwordValid) {
        passwordStar.style.display = "block";
        passwordStar.style.color = "red";
    } else {
        passwordStar.style.display = "none";
        passwordStar.style.color = "black";
    }
}

checkUsernameValidity();
usernameInput.addEventListener("keyup", () => {
    checkUsernameValidity();
});

checkPasswordValidity();
passwordInput.addEventListener("keyup", () => {
    checkPasswordValidity();
});

loginForm.addEventListener("submit", (event) => {
    if (submitInProgress) return;
    submitInProgress = true;
    event.preventDefault();
    const usernameValid = usernameInput.reportValidity();
    const passwordValid = passwordInput.reportValidity();
    const formData = new FormData(loginForm);
    if (!formData.has("username") || !formData.has("password")) {
        console.log("Username or password not provided");
        return;
    }
    if (formData.get("username").trim() === "" || formData.get("password").trim() === "") {
        console.log("Username or password is empty");
        return;
    }
    if (formData.get("username").length > 20 || formData.get("password").length > 64) {
        console.log("Username or password is too long");
        return;
    }
    if (formData.get("username").length < 3 || formData.get("password").length < 8) {
        console.log("Username or password is too short");
        return;
    }
    if (/\s/.test(formData.get("username")) || /\s/.test(formData.get("password"))) {
        console.log("Username or password contains whitespace");
        return;
    }
    if (!usernameValid || !passwordValid) {
        console.log("Invalid formatting in username or password\nUsername: " + usernameValid.validationMessage + "\n" + passwordValid.validationMessage);
        return;
    }

    const usernameValue = formData.get("username").trim();
    const passwordValue = formData.get("password").trim();

    const payload = {
        username: usernameValue,
        password: passwordValue
    };

    jsonData = JSON.stringify(payload);

    // Simulate a login request
    setTimeout(() => {
        console.log(jsonData);
        submitInProgress = false;
    }, 1000);
});