function validateRegister() {
  var f = document.forms["regForm"];
  var errors = [];

  var name = f["fullname"].value.trim();
  var email = f["email"].value.trim();
  var pw = f["password"].value;
  var confirm = f["confirm"].value;

  if (name === "") {
    errors.push("Full name is required.");
  } else if (name.length < 3) {
    errors.push("Full name must be at least 3 characters.");
  }

  if (email === "") {
    errors.push("Email is required.");
  } else if (!/^\S+@\S+\.\S+$/.test(email)) {
    errors.push("Enter a valid email address.");
  }

  if (pw.length < 8) {
    errors.push("Password must be at least 8 characters.");
  } else if (!/[A-Za-z]/.test(pw) || !/[0-9]/.test(pw)) {
    errors.push("Password must contain both letters and numbers.");
  }

  if (pw !== confirm) {
    errors.push("Passwords do not match.");
  }

  var box = document.getElementById("clientErrors");
  if (errors.length > 0) {
    box.innerHTML = "<ul class='mb-0'><li>" + errors.join("</li><li>") + "</li></ul>";
    box.classList.remove("d-none");
    return false;
  }
  box.classList.add("d-none");
  return true;
}

function validateLogin() {
  var f = document.forms["loginForm"];
  var errors = [];

  var email = f["email"].value.trim();
  var pw = f["password"].value;

  if (email === "") {
    errors.push("Email is required.");
  } else if (!/^\S+@\S+\.\S+$/.test(email)) {
    errors.push("Enter a valid email address.");
  }
  if (pw === "") {
    errors.push("Password is required.");
  }

  var box = document.getElementById("clientErrors");
  if (errors.length > 0) {
    box.innerHTML = "<ul class='mb-0'><li>" + errors.join("</li><li>") + "</li></ul>";
    box.classList.remove("d-none");
    return false;
  }
  box.classList.add("d-none");
  return true;
}

function validateTask() {
  var f = document.forms["taskForm"];
  var errors = [];

  var title = f["title"].value.trim();
  var desc = f["description"].value;
  var due = f["due_date"].value;

  if (title === "") {
    errors.push("Title is required.");
  } else if (title.length < 3) {
    errors.push("Title must be at least 3 characters.");
  } else if (title.length > 150) {
    errors.push("Title must not exceed 150 characters.");
  }

  if (desc.length > 500) {
    errors.push("Description must not exceed 500 characters.");
  }

  if (due === "") {
    errors.push("Due date is required.");
  }

  var box = document.getElementById("clientErrors");
  if (errors.length > 0) {
    box.innerHTML = "<ul class='mb-0'><li>" + errors.join("</li><li>") + "</li></ul>";
    box.classList.remove("d-none");
    return false;
  }
  box.classList.add("d-none");
  return true;
}

function validateCategory() {
  var f = document.forms["catForm"];
  var errors = [];
  var name = f["name"].value.trim();

  if (name === "") {
    errors.push("Category name is required.");
  } else if (name.length > 50) {
    errors.push("Category name must not exceed 50 characters.");
  }

  var box = document.getElementById("clientErrors");
  if (errors.length > 0) {
    box.innerHTML = "<ul class='mb-0'><li>" + errors.join("</li><li>") + "</li></ul>";
    box.classList.remove("d-none");
    return false;
  }
  box.classList.add("d-none");
  return true;
}

function validateNote() {
  var f = document.forms["noteForm"];
  var errors = [];
  var note = f["note"].value.trim();

  if (note === "") {
    errors.push("Note cannot be empty.");
  } else if (note.length > 500) {
    errors.push("Note must not exceed 500 characters.");
  }

  var box = document.getElementById("clientErrors");
  if (errors.length > 0) {
    box.innerHTML = "<ul class='mb-0'><li>" + errors.join("</li><li>") + "</li></ul>";
    box.classList.remove("d-none");
    return false;
  }
  box.classList.add("d-none");
  return true;
}

function showClientErrors(boxId, errors) {
  var box = document.getElementById(boxId);
  if (errors.length > 0) {
    box.innerHTML = "<ul class='mb-0'><li>" + errors.join("</li><li>") + "</li></ul>";
    box.classList.remove("d-none");
    return false;
  }
  box.classList.add("d-none");
  return true;
}

function validateProfile() {
  var f = document.forms["profileForm"];
  var errors = [];
  var name = f["fullname"].value.trim();
  var phone = f["phone"].value.trim();
  var bio = f["bio"].value;

  if (name === "") {
    errors.push("Full name is required.");
  } else if (name.length < 3) {
    errors.push("Full name must be at least 3 characters.");
  }
  if (phone !== "" && !/^[0-9+\-\s]{7,20}$/.test(phone)) {
    errors.push("Phone number must be 7 to 20 characters (digits, +, - and spaces only).");
  }
  if (bio.length > 255) {
    errors.push("Bio must not exceed 255 characters.");
  }
  return showClientErrors("profileClientErrors", errors);
}

function validateDelete() {
  var f = document.forms["deleteForm"];
  var errors = [];

  if (f["password"].value === "") {
    errors.push("Enter your password to confirm.");
  }
  if (!f["confirm_delete"].checked) {
    errors.push("Tick the box to confirm you understand.");
  }
  if (!showClientErrors("deleteClientErrors", errors)) {
    return false;
  }
  return confirm("This will permanently delete your account and ALL your data. Continue?");
}