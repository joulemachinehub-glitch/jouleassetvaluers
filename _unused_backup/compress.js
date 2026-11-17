const fs = require('fs');
const path = require('path');
const sharp = require('sharp');

const inputDir = __dirname;
const outputDir = path.join(__dirname, 'compressed_images');

// Create output directory if it doesn't exist
if (!fs.existsSync(outputDir)) {
    fs.mkdirSync(outputDir);
}

// Get all files in the directory
fs.readdir(inputDir, (err, files) => {
    if (err) {
        console.error('Error reading directory:', err);
        return;
    }

    // Filter for image files
    const imageFiles = files.filter(file => {
        const ext = path.extname(file).toLowerCase();
        return ['.jpg', '.jpeg', '.png', '.webp'].includes(ext);
    });

    console.log(`Found ${imageFiles.length} images. Starting compression...`);
    console.log(`Saving compressed files to: ${outputDir}\n`);

    // Process each image
    imageFiles.forEach(file => {
        const inputPath = path.join(inputDir, file);
        
        // Standardize output extension to .jpg for the HTML gallery
        const fileNameWithoutExt = path.basename(file, path.extname(file));
        const outputPath = path.join(outputDir, `${fileNameWithoutExt}.jpg`);

        // Compress and resize
        sharp(inputPath)
            .resize({ width: 1200, withoutEnlargement: true }) // Max width 1200px
            .jpeg({ quality: 70, progressive: true, mozjpeg: true }) // Highly optimized JPEG
            .toFile(outputPath)
            .then(info => {
                const originalSize = fs.statSync(inputPath).size / 1024;
                const newSize = info.size / 1024;
                console.log(`✅ Compressed: ${file} -> ${fileNameWithoutExt}.jpg | Original: ${originalSize.toFixed(2)} KB -> New: ${newSize.toFixed(2)} KB`);
            })
            .catch(err => {
                console.error(`❌ Error compressing ${file}:`, err.message);
            });
    });
});