const backgrounds = [
  'url("https://img.freepik.com/free-photo/people-exercising-gym_52683-111018.jpg?t=st=1745883497~exp=1745887097~hmac=b2a6ee8b7a8b1c8dfad435c2e8d4a02a73e502bae9c107de4831e3dc1539abc6&w=1380")',
  'url("https://img.freepik.com/free-photo/powerful-stylish-bodybuilder-with-tattoo-his-arm-doing-exercises-with-dumbbells-isolated-dark-background_613910-5209.jpg?t=st=1745844029~exp=1745847629~hmac=d263b3127c0cfa77bf626f071765d8db525f8d11c09a5de8879704467ff21e08&w=1380")',
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
