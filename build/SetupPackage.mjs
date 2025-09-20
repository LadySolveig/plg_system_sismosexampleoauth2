// @package     SimplySmart-IT Dev-Utils
// @link        https://simplysmart-it.de
// @copyright   (C) 2024 - 2025, SimplySmart-IT - Martina Scholz <https://simplysmart-it.de>. All rights reserved.

import fs from "fs";

// DO NOT DELETE THIS FILE
// This file is used by build system to build a clean extension install package.

const __dirname = import.meta.dirname;

function main() {
    const source = fs.readFileSync(__dirname + "/../package.json").toString('utf-8');
    const sourceObj = JSON.parse(source);
    sourceObj.scripts = {};
    sourceObj.scripts = {
        "postpack": "tarball=$(npm list --depth 0 | sed 's/@/-/g; s/ .*/.tgz/g; 1q;'); tar -tf $tarball | sed 's/^package\\///' | zip -@r ${npm_package_name}-${npm_package_version}.zip -x package.json; rm $tarball"
    };
    sourceObj.devDependencies = {};
    sourceObj.config = {};

    fs.writeFileSync(__dirname + "/tmp/package.json", Buffer.from(JSON.stringify(sourceObj, null, 2), "utf-8") );

    fs.copyFileSync(__dirname + "/.npmignore", __dirname + "/tmp/.npmignore");

    if (fs.existsSync(__dirname + "/../LICENSE.md")) {
        fs.copyFileSync(__dirname + "/../LICENSE.md", __dirname + "/tmp/LICENSE.txt");
    }

    if (fs.existsSync(__dirname + "/../README.md")) {
        fs.copyFileSync(__dirname + "/../README.md", __dirname + "/tmp/README.md");
    }

    if (fs.existsSync(__dirname + "/tmp/admin/script.php")) {
        fs.renameSync(__dirname + "/tmp/admin/script.php", __dirname + "/tmp/script.php");
    }

    if (fs.existsSync(__dirname + `/tmp/admin/${sourceObj.name.replace(/^com_/g, '')}.xml`)) {
        fs.renameSync(__dirname + `/tmp/admin/${sourceObj.name.replace(/^com_/g, '')}.xml`, __dirname + `/tmp/${sourceObj.name.replace(/^com_/g, '')}.xml`);
    }
}

main();
