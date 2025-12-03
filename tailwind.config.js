/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      colors: {
        primary: '#7C3AED',
        admin: '#DC2626',
        teacher: '#059669',
        student: '#3B82F6',
      }
    },
  },
  plugins: [],
}