/*
 * Interactive 3D icons (D-082) with three.js (MIT).
 *
 * Each [data-ikon-3d] holds a Codex picture (transparent WebP). One shared WebGL renderer draws every
 * visible icon in turn and copies the frame into a small 2D canvas over the picture, so the page never
 * opens more than one WebGL context. The picture floats gently, tilts towards the pointer or finger
 * over its card ([data-ikon-3d-area] or the icon itself) and bounces softly when tapped. Nothing runs
 * for icons outside the screen; with no WebGL the plain picture simply stays.
 *
 * Loaded by initIcons3d() in ui.js only when there are icons, motion is allowed and the device is strong enough.
 */
import {
    WebGLRenderer, Scene, PerspectiveCamera, PlaneGeometry, MeshBasicMaterial, Mesh, Group,
    Texture, SRGBColorSpace, LinearFilter, Color,
} from 'three';

const SIZE = 256;

function start() {
    let renderer;
    try {
        renderer = new WebGLRenderer({ alpha: true, antialias: true, preserveDrawingBuffer: true, powerPreference: 'low-power' });
    } catch {
        return;
    }
    const ratio = Math.min(window.devicePixelRatio || 1, 2);
    renderer.setPixelRatio(ratio);
    renderer.setSize(SIZE, SIZE, false);
    renderer.setClearColor(0x000000, 0);
    renderer.outputColorSpace = SRGBColorSpace;

    const geometry = new PlaneGeometry(1, 1, 1, 1);
    const icons = [];

    const pictureOf = (holder) => [...holder.querySelectorAll('img')].find((img) => img.offsetParent !== null) || holder.querySelector('img');

    function build(holder) {
        const img = pictureOf(holder);
        if (!img) {
            return;
        }
        const texture = new Texture(img);
        texture.colorSpace = SRGBColorSpace;
        texture.minFilter = LinearFilter;
        texture.generateMipmaps = false;
        const ready = () => {
            texture.needsUpdate = true;
            icon.aspect = img.naturalWidth / img.naturalHeight || 1;
            icon.fit();
            icon.dirty = true;
        };

        const scene = new Scene();
        const camera = new PerspectiveCamera(30, 1, 0.1, 10);
        camera.position.z = 2.32; // the plane fills the picture area (canvas is 124 % of it)

        const group = new Group();
        // A soft shadow copy of the same picture behind it gives the paper-cut depth.
        const shadow = new Mesh(geometry, new MeshBasicMaterial({ map: texture, color: new Color(0x0f172a), transparent: true, opacity: 0.16, depthWrite: false }));
        shadow.position.set(0.035, -0.05, -0.12);
        const front = new Mesh(geometry, new MeshBasicMaterial({ map: texture, transparent: true, depthWrite: false }));
        group.add(shadow, front);
        scene.add(group);

        const canvas = document.createElement('canvas');
        canvas.setAttribute('aria-hidden', 'true');
        holder.prepend(canvas);
        const context = canvas.getContext('2d');

        const area = holder.closest('[data-ikon-3d-area]') || holder;
        const icon = {
            holder, img, scene, camera, group, front, shadow, canvas, context, aspect: 1,
            tilt: { x: 0, y: 0 }, target: { x: 0, y: 0 }, bounce: 0, visible: false, dirty: true,
            phase: Math.random() * Math.PI * 2,
            fit() {
                const scale = 1.0;
                const w = this.aspect >= 1 ? scale : scale * this.aspect;
                const h = this.aspect >= 1 ? scale / this.aspect : scale;
                this.front.scale.set(w, h, 1);
                this.shadow.scale.set(w, h, 1);
            },
        };

        const aim = (event) => {
            const rect = holder.getBoundingClientRect();
            const x = ((event.clientX - (rect.left + rect.width / 2)) / Math.max(rect.width, 1)) * 2;
            const y = ((event.clientY - (rect.top + rect.height / 2)) / Math.max(rect.height, 1)) * 2;
            icon.target.x = Math.max(-1, Math.min(1, y)) * 0.38;
            icon.target.y = Math.max(-1, Math.min(1, x)) * 0.5;
        };
        area.addEventListener('pointermove', aim);
        area.addEventListener('pointerdown', (event) => {
            aim(event);
            icon.bounce = 1;
        });
        area.addEventListener('pointerleave', () => {
            icon.target.x = 0;
            icon.target.y = 0;
        });
        area.addEventListener('focusin', () => {
            icon.bounce = 1;
        });

        if (img.complete && img.naturalWidth > 0) {
            ready();
        } else {
            img.addEventListener('load', ready, { once: true });
        }
        holder.classList.add('is-3d');
        icons.push(icon);
        observer.observe(holder);
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            const icon = icons.find((item) => item.holder === entry.target);
            if (icon) {
                icon.visible = entry.isIntersecting;
            }
        });
    }, { rootMargin: '80px' });

    const sizeCanvas = (icon) => {
        const rect = icon.canvas.getBoundingClientRect();
        const w = Math.max(1, Math.round(rect.width * ratio));
        const h = Math.max(1, Math.round(rect.height * ratio));
        if (icon.canvas.width !== w || icon.canvas.height !== h) {
            icon.canvas.width = w;
            icon.canvas.height = h;
        }
    };

    let last = performance.now();
    function frame(now) {
        const dt = Math.min(0.05, (now - last) / 1000);
        last = now;
        const t = now / 1000;
        for (const icon of icons) {
            if (!icon.visible) {
                continue;
            }
            // Springy follow of the pointer, slow float, a short bounce after a tap.
            icon.tilt.x += (icon.target.x - icon.tilt.x) * Math.min(1, dt * 7);
            icon.tilt.y += (icon.target.y - icon.tilt.y) * Math.min(1, dt * 7);
            icon.bounce = Math.max(0, icon.bounce - dt * 2.2);
            const pop = Math.sin(icon.bounce * Math.PI) * 0.08;
            icon.group.rotation.x = icon.tilt.x + Math.sin(t * 0.9 + icon.phase) * 0.05;
            icon.group.rotation.y = icon.tilt.y + Math.cos(t * 0.7 + icon.phase) * 0.08;
            icon.group.rotation.z = Math.sin(t * 0.6 + icon.phase) * 0.02;
            icon.group.position.y = Math.sin(t * 1.3 + icon.phase) * 0.035 + pop * 0.4;
            icon.group.scale.setScalar(1 + pop);
            icon.shadow.position.x = 0.035 - icon.tilt.y * 0.06;
            icon.shadow.position.y = -0.05 + icon.tilt.x * 0.06 - icon.group.position.y * 0.5;

            sizeCanvas(icon);
            icon.camera.aspect = icon.canvas.width / icon.canvas.height;
            icon.camera.updateProjectionMatrix();
            renderer.render(icon.scene, icon.camera);
            icon.context.clearRect(0, 0, icon.canvas.width, icon.canvas.height);
            icon.context.drawImage(renderer.domElement, 0, 0, icon.canvas.width, icon.canvas.height);
        }
        window.requestAnimationFrame(frame);
    }

    document.querySelectorAll('[data-ikon-3d]').forEach(build);

    // The theme toggle swaps light/dark pictures: point the texture at the one now visible.
    new MutationObserver(() => {
        icons.forEach((icon) => {
            const img = pictureOf(icon.holder);
            if (img && img !== icon.img) {
                icon.img = img;
                icon.front.material.map.image = img;
                icon.front.material.map.needsUpdate = true;
            }
        });
    }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

    // Stop when the visitor turns reduced motion on.
    const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
    motion.addEventListener?.('change', () => {
        if (motion.matches) {
            icons.forEach((icon) => {
                icon.visible = false;
                icon.canvas.remove();
                icon.holder.classList.remove('is-3d');
            });
        }
    });

    window.requestAnimationFrame(frame);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
} else {
    start();
}
