async function createEvaluationScene() {
  const host = document.querySelector('[data-overview-scene]');
  if (!host) return;

  let THREE;
  try {
    THREE = await import('https://cdn.jsdelivr.net/npm/three@0.180.0/build/three.module.js');
  } catch {
    host.classList.add('is-fallback');
    return;
  }

  const canvas = host.querySelector('.overview-scene-canvas');
  let renderer;
  try {
    renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true, powerPreference: 'low-power' });
  } catch {
    host.classList.add('is-fallback');
    return;
  }
  renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.5));
  renderer.outputColorSpace = THREE.SRGBColorSpace;
  renderer.toneMapping = THREE.ACESFilmicToneMapping;
  renderer.toneMappingExposure = 1.15;

  const scene = new THREE.Scene();
  const camera = new THREE.PerspectiveCamera(34, 1, 0.1, 40);
  camera.position.set(0, 0, 8.3);

  scene.add(new THREE.HemisphereLight(0xf6eedb, 0x23372e, 2.1));
  const keyLight = new THREE.DirectionalLight(0xffedc8, 2.8);
  keyLight.position.set(-3, 4, 7);
  scene.add(keyLight);
  const fillLight = new THREE.DirectionalLight(0xc0d1c4, 1.2);
  fillLight.position.set(4, -1, 5);
  scene.add(fillLight);

  const pageCanvas = document.createElement('canvas');
  pageCanvas.width = 780;
  pageCanvas.height = 1000;
  const context = pageCanvas.getContext('2d');
  context.fillStyle = '#f5f1e6';
  context.fillRect(0, 0, pageCanvas.width, pageCanvas.height);
  context.fillStyle = '#315a49';
  context.fillRect(0, 0, pageCanvas.width, 140);
  context.fillStyle = '#d8c69e';
  context.beginPath();
  context.arc(78, 70, 20, 0, Math.PI * 2);
  context.fill();
  context.fillStyle = '#f5f1e6';
  context.font = '700 24px Arial, sans-serif';
  context.fillText('SCHOOL FEEDBACK', 120, 67);
  context.fillStyle = '#d9dfd3';
  context.font = '500 15px Arial, sans-serif';
  context.fillText('TEACHING PRACTICE  /  REVIEW COPY', 120, 96);

  context.fillStyle = '#867b63';
  context.font = '700 16px Arial, sans-serif';
  context.fillText('CLASSROOM EXPERIENCE', 58, 196);
  context.strokeStyle = '#d8d5ca';
  context.lineWidth = 2;
  context.beginPath();
  context.moveTo(58, 220);
  context.lineTo(722, 220);
  context.stroke();

  const rubricRows = [
    ['Clear explanations', 0.86],
    ['Welcoming classroom', 0.74],
    ['Prepared lessons', 0.92],
    ['Helpful feedback', 0.81],
  ];
  rubricRows.forEach(([label, score], index) => {
    const rowY = 280 + index * 130;
    context.fillStyle = '#34453a';
    context.font = '600 23px Arial, sans-serif';
    context.fillText(label, 58, rowY);
    context.fillStyle = '#ded9ca';
    context.fillRect(58, rowY + 25, 500, 12);
    context.fillStyle = index % 2 ? '#9aab8d' : '#b39a68';
    context.fillRect(58, rowY + 25, 500 * score, 12);
    context.fillStyle = '#7d826f';
    context.font = '700 16px Arial, sans-serif';
    context.fillText(`${Math.round(score * 5)}/5`, 625, rowY + 39);
  });

  context.strokeStyle = '#ddd8cb';
  context.beginPath();
  context.moveTo(58, 820);
  context.lineTo(722, 820);
  context.stroke();
  context.fillStyle = '#8c826b';
  context.font = '600 15px Arial, sans-serif';
  context.fillText('CONFIDENTIAL  •  AGGREGATE RESULTS', 58, 866);
  context.fillStyle = '#315a49';
  context.fillRect(58, 900, 174, 12);
  context.fillStyle = '#d6d1c4';
  context.fillRect(244, 900, 478, 12);

  const pageTexture = new THREE.CanvasTexture(pageCanvas);
  pageTexture.colorSpace = THREE.SRGBColorSpace;
  const paper = new THREE.MeshStandardMaterial({ color: 0xffffff, roughness: 0.92 });
  const cover = new THREE.MeshStandardMaterial({ color: 0x315447, roughness: 0.88 });
  const front = new THREE.MeshStandardMaterial({ color: 0xffffff, map: pageTexture, roughness: 0.95 });
  const backing = new THREE.Mesh(
    new THREE.BoxGeometry(2.42, 3.12, 0.1),
    [cover, cover, cover, cover, cover, cover]
  );
  backing.position.set(-0.04, 0.03, -0.16);
  backing.rotation.z = -0.035;

  const sheets = new THREE.Group();
  for (let layer = 0; layer < 3; layer += 1) {
    const sheet = new THREE.Mesh(
      new THREE.BoxGeometry(2.27, 2.98, 0.025),
      [paper, paper, paper, paper, paper, paper]
    );
    sheet.position.set(-0.025 * layer, -0.025 * layer, -0.09 + layer * 0.025);
    sheet.rotation.z = -0.02;
    sheets.add(sheet);
  }

  const page = new THREE.Mesh(
    new THREE.BoxGeometry(2.27, 2.98, 0.045),
    [paper, paper, paper, paper, front, paper]
  );
  page.position.set(0, 0, -0.005);
  page.rotation.z = -0.02;

  const pencil = new THREE.Group();
  const wood = new THREE.MeshStandardMaterial({ color: 0xb99a64, roughness: 0.58 });
  const graphite = new THREE.MeshStandardMaterial({ color: 0x343b35, roughness: 0.8 });
  const brass = new THREE.MeshStandardMaterial({ color: 0xd4bd87, metalness: 0.48, roughness: 0.4 });
  const pencilBody = new THREE.Mesh(new THREE.CylinderGeometry(0.045, 0.045, 2.2, 10), wood);
  pencilBody.position.y = 0.03;
  pencil.add(pencilBody);
  const pencilTip = new THREE.Mesh(new THREE.ConeGeometry(0.045, 0.19, 10), graphite);
  pencilTip.position.y = -1.16;
  pencilTip.rotation.z = Math.PI;
  pencil.add(pencilTip);
  const pencilBand = new THREE.Mesh(new THREE.CylinderGeometry(0.047, 0.047, 0.1, 10), brass);
  pencilBand.position.y = 1.05;
  pencil.add(pencilBand);
  const eraser = new THREE.Mesh(
    new THREE.CylinderGeometry(0.045, 0.045, 0.18, 10),
    new THREE.MeshStandardMaterial({ color: 0x9b6d59, roughness: 0.78 })
  );
  eraser.position.y = 1.18;
  pencil.add(eraser);
  pencil.position.set(1.35, -0.04, 0.12);
  pencil.rotation.z = -0.32;

  const artifact = new THREE.Group();
  artifact.add(backing, sheets, page, pencil);
  scene.add(artifact);

  const shadow = new THREE.Mesh(
    new THREE.CircleGeometry(1, 48),
    new THREE.MeshBasicMaterial({ color: 0x111a15, transparent: true, opacity: 0.22, depthWrite: false })
  );
  shadow.scale.set(1.65, 0.22, 1);
  shadow.position.set(0.1, -1.72, -0.4);
  scene.add(shadow);

  let targetX = -0.12;
  let targetY = -0.23;
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  artifact.rotation.set(reducedMotion ? targetX : -0.28, reducedMotion ? targetY : 0.24, -0.025);

  const resizeScene = () => {
    const bounds = host.getBoundingClientRect();
    const width = Math.max(1, Math.round(bounds.width));
    const height = Math.max(1, Math.round(bounds.height));
    renderer.setSize(width, height, false);
    camera.aspect = width / height;
    camera.updateProjectionMatrix();
    requestFrame();
  };

  let frame = 0;
  let isVisible = true;
  let introStart = performance.now();
  let pointerStart = null;
  const startRotation = { x: artifact.rotation.x, y: artifact.rotation.y };
  let introActive = !reducedMotion;

  const renderFrame = (now) => {
    frame = 0;
    if (!isVisible) return;
    if (introActive) {
      const progress = Math.min(1, (now - introStart) / 780);
      const ease = 1 - (1 - progress) ** 3;
      artifact.rotation.x = startRotation.x + (targetX - startRotation.x) * ease;
      artifact.rotation.y = startRotation.y + (targetY - startRotation.y) * ease;
      if (progress >= 1) introActive = false;
    }
    artifact.rotation.x += (targetX - artifact.rotation.x) * 0.14;
    artifact.rotation.y += (targetY - artifact.rotation.y) * 0.14;
    renderer.render(scene, camera);
    if (introActive || Math.abs(artifact.rotation.x - targetX) > 0.001 || Math.abs(artifact.rotation.y - targetY) > 0.001) {
      requestFrame();
    }
  };

  function requestFrame() {
    if (!frame && isVisible) frame = window.requestAnimationFrame(renderFrame);
  }

  canvas.addEventListener('pointerdown', (event) => {
    if (event.button !== 0) return;
    pointerStart = { x: event.clientX, y: event.clientY, rotationX: targetX, rotationY: targetY };
    canvas.setPointerCapture(event.pointerId);
  });
  canvas.addEventListener('pointermove', (event) => {
    if (!pointerStart) return;
    targetY = pointerStart.rotationY + (event.clientX - pointerStart.x) * 0.008;
    targetX = THREE.MathUtils.clamp(pointerStart.rotationX + (event.clientY - pointerStart.y) * 0.006, -0.34, 0.34);
    requestFrame();
  });
  const endDrag = () => { pointerStart = null; };
  canvas.addEventListener('pointerup', endDrag);
  canvas.addEventListener('pointercancel', endDrag);

  host.querySelectorAll('[data-scene-turn]').forEach((button) => {
    button.addEventListener('click', () => {
      targetY += Number(button.dataset.sceneTurn) * 0.42;
      requestFrame();
    });
  });

  const visibilityObserver = new IntersectionObserver(([entry]) => {
    isVisible = entry.isIntersecting;
    if (isVisible) requestFrame();
  }, { threshold: 0.01 });
  visibilityObserver.observe(host);
  new ResizeObserver(resizeScene).observe(host);
  document.addEventListener('visibilitychange', () => {
    isVisible = !document.hidden;
    if (isVisible) requestFrame();
  });
  host.classList.add('is-ready');
  resizeScene();
}

createEvaluationScene();