const css = `
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root {
    --navy: #0F1B2D;
    --navy2: #162236;
    --blue: #2D7DD2;
    --blue-light: #4A90E2;
    --blue-pale: #EBF4FF;
    --slate: #64748B;
    --slate2: #94A3B8;
    --light: #F0F4FA;
    --border: #E2E8F0;
}
body { font-family: 'Inter', sans-serif; background: var(--light); color: var(--navy); line-height: 1.5; }


  /* LANDING */
  .landing { background: var(--navy); min-height: 100vh; display: flex; flex-direction: column; }
  .nav-bar { display: flex; align-items: center; justify-content: space-between; padding: 0 40px; height: 72px; border-bottom: 1px solid rgba(255,255,255,0.07); }
  .logo { font-size: 22px; font-weight: 800; color: var(--white); letter-spacing: -0.5px; }
  .logo span { color: var(--blue-light); }