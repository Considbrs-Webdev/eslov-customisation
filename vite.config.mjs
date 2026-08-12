import { createViteConfig } from "vite-config-factory";

const entries = {
    "css/site-overrides": "./source/sass/site-overrides.scss",
    "js/site": "./source/js/site.js",
};

export default createViteConfig(entries, {
    outDir: "assets/dist",
    manifestFile: "manifest.json",
});
