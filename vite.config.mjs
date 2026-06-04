import { createViteConfig } from "vite-config-factory";

const entries = {
    "css/mod-navigation": "./source/sass/mod-navigation.scss",
};

export default createViteConfig(entries, {
    outDir: "assets/dist",
    manifestFile: "manifest.json",
});
