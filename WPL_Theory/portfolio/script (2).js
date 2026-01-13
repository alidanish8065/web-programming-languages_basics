const viewCvBtn = document.getElementById("viewCvBtn");
const homeSection = document.getElementById("home");
const cvSection = document.getElementById("cvSection");

viewCvBtn.addEventListener("click", () => {
  // Move the home section upward
  homeSection.style.transform = "translateY(-100vh)";
  
  // After the animation, show the CV
  setTimeout(() => {
    homeSection.style.display = "none";
    cvSection.classList.remove("hidden");
    cvSection.classList.add("visible");
  }, 900);
});