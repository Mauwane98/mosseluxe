/** @type {import('tailwindcss').Config} */
module.exports = {
  theme: {
    extend: {
      colors: {
        'custom-black': '#000028',
      },
    },
  },
  content: [
    "./*.php",
    "./admin/*.php",
    "./includes/*.php",
    "./assets/js/*.js",
  ],
  theme: {
    extend: {
      zIndex: {
        '0': '0',
        '10': '10',
        '20': '20',
        '30': '30',
        '40': '40',
        '50': '50',
        '60': '60',
        '70': '70',
        '80': '80',
        '90': '90',
        '100': '100',
      },
    },
  },
  plugins: [],
}
