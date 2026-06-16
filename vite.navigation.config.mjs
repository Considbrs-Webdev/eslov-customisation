import { createViteConfig } from "vite-config-factory";

const entries = {
    "css/mod-navigation": "./source/php/Modules/Navigation/sass/mod-navigation.scss",
};

export default createViteConfig(entries, {
    outDir: "source/php/Modules/Navigation/assets/dist",
    manifestFile: "manifest.json",
});
