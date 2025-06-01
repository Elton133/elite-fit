const backgrounds = [
  'url("https://cdn.pixabay.com/photo/2025/05/21/16/49/ai-generated-9614198_1280.png")',
  'url("https://cdn.pixabay.com/photo/2024/03/30/19/29/ai-generated-8665327_1280.png")',
  'url("https://img.freepik.com/free-photo/young-fitness-man-studio_7502-5008.jpg?t=st=1745841790~exp=1745845390~hmac=f7e33bc8565b5f214a4971438c6aa2885abc048ca6bc7e7257ff4576bf86ccac&w=1380")',
];

let currentBg = 0;
const bgElement = document.querySelector(".background");

function changeBackground() {
  bgElement.style.backgroundImage = backgrounds[currentBg];
  currentBg = (currentBg + 1) % backgrounds.length;
}

changeBackground();
setInterval(changeBackground, 8000);
