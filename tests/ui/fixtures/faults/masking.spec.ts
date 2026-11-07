import { expect, test } from '@playwright/test';

import { maskedScreenshotOptions } from '../../src/sanitize.mjs';

const CANARY = 'CANARY_MASK_SECRET_9c4e17';
const MASK_COLOR = [255, 0, 255];

test('masked screenshots replace the sensitive region instead of rendering its value', async ({ page }) => {
  await page.setViewportSize({ width: 800, height: 600 });
  await page.setContent(
    [
      '<!doctype html><html><body style="margin:0;background:#ffffff">',
      `<div id="pass" data-sensitive style="position:absolute;left:120px;top:140px;width:320px;height:120px;background:#ffffff;color:#000000;font:24px/34px monospace;overflow:hidden">${CANARY} ${CANARY}</div>`,
      '</body></html>',
    ].join(''),
  );

  const sensitive = page.locator('#pass');
  const box = await sensitive.boundingBox();

  expect(box).not.toBeNull();
  expect(await sensitive.textContent()).toContain(CANARY);

  const clip = {
    x: Math.round(box.x),
    y: Math.round(box.y),
    width: Math.round(box.width),
    height: Math.round(box.height),
  };
  const options = maskedScreenshotOptions();
  const screenshotOptions = {
    animations: options.animations,
    caret: options.caret,
    fullPage: options.fullPage,
    scale: options.scale,
  };
  const mask = options.mask.map((selector) => page.locator(selector));
  const masked = await page.screenshot({ ...screenshotOptions, clip, mask });
  const unmasked = await page.screenshot({ ...screenshotOptions, clip });

  const comparison = await page.evaluate(
    async ({ maskedDataUrl, unmaskedDataUrl, width, height, maskColor }) => {
      const loadPixels = (dataUrl) =>
        new Promise<Uint8ClampedArray>((resolve, reject) => {
          const image = new Image();

          image.onload = () => {
            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            const context = canvas.getContext('2d');

            if (!context) {
              reject(new Error('2D canvas is unavailable'));
              return;
            }

            context.drawImage(image, 0, 0);
            resolve(context.getImageData(0, 0, width, height).data);
          };
          image.onerror = () => reject(new Error('screenshot PNG could not be decoded'));
          image.src = dataUrl;
        });
      const [maskedPixels, unmaskedPixels] = await Promise.all([
        loadPixels(maskedDataUrl),
        loadPixels(unmaskedDataUrl),
      ]);
      let interiorPixels = 0;
      let interiorMaskMismatches = 0;
      let differentPixels = 0;

      for (let y = 0; y < height; y += 1) {
        for (let x = 0; x < width; x += 1) {
          const index = (y * width + x) * 4;
          const isMaskColor =
            maskedPixels[index] === maskColor[0] &&
            maskedPixels[index + 1] === maskColor[1] &&
            maskedPixels[index + 2] === maskColor[2];

          if (x > 0 && x < width - 1 && y > 0 && y < height - 1) {
            interiorPixels += 1;

            if (!isMaskColor) {
              interiorMaskMismatches += 1;
            }
          }

          if (
            maskedPixels[index] !== unmaskedPixels[index] ||
            maskedPixels[index + 1] !== unmaskedPixels[index + 1] ||
            maskedPixels[index + 2] !== unmaskedPixels[index + 2]
          ) {
            differentPixels += 1;
          }
        }
      }

      return { totalPixels: width * height, interiorPixels, interiorMaskMismatches, differentPixels };
    },
    {
      maskedDataUrl: `data:image/png;base64,${masked.toString('base64')}`,
      unmaskedDataUrl: `data:image/png;base64,${unmasked.toString('base64')}`,
      width: clip.width,
      height: clip.height,
      maskColor: MASK_COLOR,
    },
  );

  expect(comparison.interiorMaskMismatches).toBe(0);
  expect(comparison.interiorPixels).toBeGreaterThan(0);
  expect(comparison.differentPixels).toBeGreaterThan(comparison.totalPixels * 0.9);
});
