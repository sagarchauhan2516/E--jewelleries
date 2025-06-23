// try.js (Full Refined Code)
console.log('try.js loaded!');

const videoElement = document.getElementById('webcamVideo');
const videoCanvas = document.getElementById('videoCanvas');
const canvasCtx = videoCanvas.getContext('2d');
const threeCanvas = document.getElementById('threeCanvas');
const renderer = new THREE.WebGLRenderer({ canvas: threeCanvas, alpha: true });

let scene, cameraThreeJS, ringModel = null;

videoElement.addEventListener('loadedmetadata', () => {
    resizeCanvas();
    initThreeJS();
});

function resizeCanvas() {
    const width = videoElement.videoWidth;
    const height = videoElement.videoHeight;
    videoCanvas.width = width;
    videoCanvas.height = height;
    threeCanvas.width = width;
    threeCanvas.height = height;
    renderer.setSize(width, height);
    renderer.setPixelRatio(window.devicePixelRatio);
}

function initThreeJS() {
    scene = new THREE.Scene();
    cameraThreeJS = new THREE.PerspectiveCamera(75, threeCanvas.width / threeCanvas.height, 0.1, 1000);
    cameraThreeJS.position.set(0, 0, 1.5);

    scene.add(new THREE.AmbientLight(0xffffff, 1));
    const light = new THREE.DirectionalLight(0xffffff, 1);
    light.position.set(0, 1, 1).normalize();
    scene.add(light);

    const loader = new THREE.GLTFLoader();
    loader.load('../Jewellery-models/memories_wooden_ring.glb',
        (gltf) => {
            ringModel = gltf.scene;
            ringModel.scale.set(0.04, 0.04, 0.04);
            scene.add(ringModel);
            console.log('Ring model loaded!');
        },
        undefined,
        (error) => console.error('Model load error:', error)
    );

    function animate() {
        requestAnimationFrame(animate);
        renderer.render(scene, cameraThreeJS);
    }
    animate();
}

function toWorld(pt) {
    const x = (pt.x - 0.5) * cameraThreeJS.aspect * 2 * cameraThreeJS.position.z;
    const y = (0.5 - pt.y) * 2 * cameraThreeJS.position.z;
    const z = -pt.z * 2;
    return new THREE.Vector3(x, y, z);
}

const hands = new Hands({
    locateFile: (file) => `https://cdn.jsdelivr.net/npm/@mediapipe/hands/${file}`,
});
hands.setOptions({
    maxNumHands: 1,
    modelComplexity: 1,
    minDetectionConfidence: 0.5,
    minTrackingConfidence: 0.5,
});

hands.onResults((results) => {
    canvasCtx.clearRect(0, 0, videoCanvas.width, videoCanvas.height);
    canvasCtx.drawImage(results.image, 0, 0, videoCanvas.width, videoCanvas.height);

    if (results.multiHandLandmarks?.length && ringModel) {
        const landmarks = results.multiHandLandmarks[0];
        const base = toWorld(landmarks[13]);
        const mid = toWorld(landmarks[14]);

        const direction = new THREE.Vector3().subVectors(mid, base).normalize();
        const quaternion = new THREE.Quaternion().setFromUnitVectors(new THREE.Vector3(0, 1, 0), direction);

        ringModel.position.copy(base);
        ringModel.quaternion.copy(quaternion);

        // Final refined offset
        const directionOffset = direction.clone().multiplyScalar(-0.018);
        const liftOffset = new THREE.Vector3(0, -0.005, 0);
        const depthFix = new THREE.Vector3(0, 0, -0.005);
        ringModel.position.add(directionOffset).add(liftOffset).add(depthFix);
    }
});

const camera = new Camera(videoElement, {
    onFrame: async () => {
        await hands.send({ image: videoElement });
    },
    width: 640,
    height: 480,
});
camera.start();

window.addEventListener('resize', resizeCanvas);
