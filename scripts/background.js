const backgrounds = [
  'url("https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=1470&q=80")',
  'url("https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&w=1470&q=80")',
  'url("https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?auto=format&fit=crop&w=1470&q=80")',
];

let currentBg = 0;
const bgElement = document.querySelector(".background");

function changeBackground() {
  bgElement.style.backgroundImage = backgrounds[currentBg];
  currentBg = (currentBg + 1) % backgrounds.length;
}

changeBackground();
setInterval(changeBackground, 8000);
