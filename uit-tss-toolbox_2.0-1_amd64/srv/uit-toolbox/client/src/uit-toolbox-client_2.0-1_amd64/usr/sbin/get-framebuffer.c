#include <unistd.h>
#include <stdio.h>
#include <fcntl.h>
#include <stdlib.h>
#include <linux/fb.h>
#include <sys/mman.h>
#include <sys/ioctl.h>
#include <stdint.h>
#include <jpeglib.h>


int main () {
    uint8_t *fb_data = NULL;
    uint8_t *rgb888_buffer = NULL;
    int x = 0, y = 0;

    struct fb_var_screeninfo vinfo;
    struct fb_fix_screeninfo finfo;

    struct jpeg_compress_struct cinfo;
    struct jpeg_error_mgr jerr;
    FILE *outfile;
    JSAMPROW row_pointer[1];
    int jpeg_quality = 100;


    int fbfd = open("/dev/fb0", O_RDONLY);
    if (fbfd < 0) {
        perror("Can't open /dev/fb0");
        return 1;
    }

    if (ioctl(fbfd, FBIOGET_FSCREENINFO, &finfo)) {
        printf("%s\n", "Cannot read fixed screen info");
        close(fbfd);
        return 1;
    }

    if (ioctl(fbfd, FBIOGET_VSCREENINFO, &vinfo)) {
        printf("%s\n", "Cannot read variable screen info");
        close(fbfd);
        return 1;
    }

    int fb_width = vinfo.xres;
    int fb_height = vinfo.yres;
    int fb_bits_per_pixel = vinfo.bits_per_pixel;
    int fb_bytes_per_pixel = fb_bits_per_pixel / 8;
    long int fb_size = finfo.smem_len;
    // long int fb_size = fb_width * fb_height * fb_bytes_per_pixel;

    if (vinfo.bits_per_pixel != 32) {
        fprintf(stderr, "Error: Expected 32 bits per pixel, but found %d.\n", fb_bits_per_pixel);
        close(fbfd);
        return 1;
    }

    fb_data = (uint8_t *)mmap(0, fb_size, PROT_READ, MAP_SHARED, fbfd, 0);
    if (fb_data == MAP_FAILED) {
        perror("Error mmapping framebuffer");
        close(fbfd);
        return 1;
    }
    printf("Screen resolution: %dx%d (%ld bytes)\n", fb_width, fb_height, fb_size);

    // Image conversion
    rgb888_buffer = (uint8_t *)malloc(fb_width * fb_height * 3);
    if (!rgb888_buffer) {
        perror("Failed to allocate RGB888 buffer");
        free(rgb888_buffer);
        munmap(fb_data, fb_size);
        close(fbfd);
        return 1;
    }

    uint32_t *src_pixel_ptr = (uint32_t *)fb_data;
    uint8_t *dest_pixel_ptr = rgb888_buffer;

    int jpeg_processing_range_y = fb_height / 2;
    int jpeg_processing_range_x = fb_width / 2;
    for (int y = 0; y < fb_height; y++) {
        for (int x = 0; x < fb_width; x++) {
            uint32_t pixel_value = src_pixel_ptr[x];
            // Take last 2 bits off 8-bit pixels to convert to 24-bit color.
            *dest_pixel_ptr++ = (uint8_t)((pixel_value >> vinfo.red.offset) & 0xFF);
            *dest_pixel_ptr++ = (uint8_t)((pixel_value >> vinfo.green.offset) & 0xFF);
            *dest_pixel_ptr++ = (uint8_t)((pixel_value >> vinfo.blue.offset) & 0xFF);
        }
        src_pixel_ptr = (uint32_t *)((uint8_t *)src_pixel_ptr + finfo.line_length);
    }

    outfile = fopen("/tmp/test-jpeg.jpeg", "wb");
    if (!outfile) {
        perror("Error opening output JPEG file");
        free(rgb888_buffer);
        munmap(fb_data, fb_size);
        close(fbfd);
        return 1;
    }


    cinfo.err = jpeg_std_error(&jerr);
    jpeg_create_compress(&cinfo);
    jpeg_stdio_dest(&cinfo, outfile);

    cinfo.image_width = fb_width;
    cinfo.image_height = fb_height;
    cinfo.input_components = 3; // R, G, B
    cinfo.in_color_space = JCS_RGB;

    jpeg_set_defaults(&cinfo);
    jpeg_set_quality(&cinfo, jpeg_quality, TRUE);

    jpeg_start_compress(&cinfo, TRUE);

    dest_pixel_ptr = rgb888_buffer;
    while (cinfo.next_scanline < cinfo.image_height) {
        row_pointer[0] = dest_pixel_ptr + cinfo.next_scanline * vinfo.xres * 3;
        (void) jpeg_write_scanlines(&cinfo, row_pointer, 1);
    }

    jpeg_finish_compress(&cinfo);
    fclose(outfile);
    jpeg_destroy_compress(&cinfo);
    free(rgb888_buffer);
    munmap(fb_data, fb_size);
    close(fbfd);

    return 0;
}