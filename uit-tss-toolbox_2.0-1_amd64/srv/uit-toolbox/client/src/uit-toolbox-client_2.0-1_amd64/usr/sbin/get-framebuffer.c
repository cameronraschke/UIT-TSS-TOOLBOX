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
    long int screensize = 0;
    char *fb_buff = 0;
    int x = 0, y = 0;
    long int location = 0;

    struct fb_var_screeninfo vinfo;
    struct fb_fix_screeninfo finfo;

    int fbfd = open("/dev/fb0", O_RDONLY);
    if (fbfd < 0) {
            perror("Can't open /dev/fb0");
            return 1;
    }

    if (ioctl(fbfd, FBIOGET_FSCREENINFO, &finfo)) {
        printf("%s\n", "Cannot read fixed screen info");
        return 1;
    }


    if (ioctl(fbfd, FBIOGET_VSCREENINFO, &vinfo)) {
        printf("%s\n", "Cannot read variable screen info");
        return 1;
    }

    int fb_width = vinfo.xres;
    int fb_height = vinfo.yres;
    int fb_bits_per_pixel = vinfo.bits_per_pixel;
    int fb_bytes_per_pixel = fb_bits_per_pixel / 8;
    //long int fb_size = fixedFbInfo.smem_len;
    long int fb_size = fb_width * fb_height * fb_bytes_per_pixel;


    // Get bytes of screen for mmap
    //long int screenBytes = screenWidth * screenHeight * (bytesPerPixel / 8);
    //off_t screenBytes = lseek(fbfd, 0, SEEK_END);
    fb_buff = (char *)mmap(0, fb_size, PROT_READ, MAP_SHARED, fbfd, 0);
    if ((long int)fb_buff == -1) {
            perror("Error mapping framebuffer");
            close(fbfd);
            return 1;
    }
    printf("Screen resolution: %dx%d (%ld bytes)\n", fb_width, fb_height, fb_size);

    // Image conversion
    struct jpeg_compress_struct cinfo;
    struct jpeg_error_mgr jerr;
    FILE *outfile = fopen("/tmp/test-jpeg.jpeg", "wb");
    JSAMPROW row_pointer[1];
    int row_stride;
    int jpeg_quality = 100;

    cinfo.err = jpeg_std_error(&jerr);
    //jerr.error_exit = jpeg_error_exit;
    jpeg_create_compress(&cinfo);

    if (outfile == NULL) {
        perror("Cannot open jpeg output");
        munmap(fb_buff, fb_size);
        close(fbfd);
        return 1;
    }

    jpeg_stdio_dest(&cinfo, outfile);
    cinfo.image_width = fb_width;
    cinfo.image_height = fb_height;
    cinfo.input_components = 3; //R, G, B
    cinfo.in_color_space = JCS_RGB;

    jpeg_set_defaults(&cinfo);
    jpeg_set_quality(&cinfo, jpeg_quality, TRUE);

    jpeg_start_compress(&cinfo, TRUE);

    row_stride = fb_width * 3; //R, G, B

    unsigned char *rgb_row_buffer = (unsigned char *)malloc(row_stride);
    if (!rgb_row_buffer) {
        perror("Cannot allocate row buffer");
        jpeg_destroy_compress(&cinfo);
        fclose(outfile);
        munmap(fb_buff, fb_size);
        close(fbfd);
        return 1;
    }

    while (cinfo.next_scanline < cinfo.image_height) {
        for (x = 0; x < vinfo.xres; x++) {
            location = (x + vinfo.xoffset) * (vinfo.bits_per_pixel / 8) +
                    (cinfo.next_scanline + vinfo.yoffset) * finfo.line_length;
            if (vinfo.bits_per_pixel == 32 || vinfo.bits_per_pixel == 24) {
                rgb_row_buffer[x * 3 + 0] = fb_buff[location + 2]; // Red
                rgb_row_buffer[x * 3 + 1] = fb_buff[location + 1]; // Green
                rgb_row_buffer[x * 3 + 2] = fb_buff[location + 0]; //Red
            } else {
                perror("Cannot read non-32 bit colors");
                break;
            }
        }
        row_pointer[0] = rgb_row_buffer;
        (void)jpeg_write_scanlines(&cinfo, row_pointer, 1);
    }

    free(rgb_row_buffer);

    jpeg_finish_compress(&cinfo);
    fclose(outfile);
    jpeg_destroy_compress(&cinfo);
    munmap(fb_buff, fb_size);
    close(fbfd);
    return 0;
}