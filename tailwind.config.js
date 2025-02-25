/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./src/**/*.{html,php,js}",
    "./public/**/*.{html,php,js}",
    "./src/Components/**/*.{html,php,js}"
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#f0f6ff',
          100: '#1a365d',
          200: '#15294a',
          300: '#102037',
        },
        secondary: {
          50: '#f7f8fa',
          100: '#2d3748',
          200: '#242d3a',
          300: '#1b222c',
        },
        accent: {
          50: '#fff5eb',
          100: '#ed8936',
          200: '#e67e2e',
          300: '#df7326',
        }
      },
      fontFamily: {
        sans: ['Inter', 'Noto Sans JP', 'sans-serif'],
        heading: ['Montserrat', 'Noto Sans JP', 'sans-serif'],
      },
      borderRadius: {
        'custom': '0.625rem',
      }
    },
  },
  plugins: [
    require('daisyui'),
  ],
  daisyui: {
    themes: [
      {
        carmarket: {
          "primary": "#1a365d",
          "primary-focus": "#15294a",
          "primary-content": "#ffffff",
          "secondary": "#2d3748",
          "secondary-focus": "#242d3a",
          "secondary-content": "#ffffff",
          "accent": "#ed8936",
          "accent-focus": "#e67e2e",
          "accent-content": "#ffffff",
          "neutral": "#3d4451",
          "base-100": "#ffffff",
          "base-200": "#f8f9fa",
          "base-300": "#f1f2f4",
          "base-content": "#1f2937",
        },
      },
      "light",
      "dark"
    ],
  }
}

