#include <unistd.h>
#include <stdio.h>
#include <fcntl.h>
#include <stdlib.h>
#include <linux/fb.h>
#include <sys/mman.h>
#include <sys/ioctl.h>
#include <stdint.h>
#include <jpeglib.h>
#include <pthread.h>
#include <signal.h>
#include <sys/time.h>

typedef struct {
    const uint8_t *src_fb_data;
    uint8_t *dest_rgb888_data;
    int start_y;
    int end_y;
    int width;
    int line_length; // Framebuffer line length (stride)
    struct fb_var_screeninfo vinfo; // Needed for bitfield offsets
} ThreadArgs;

volatile int running = 1;

void sigint_handler(int signo) {
    printf("Caught SIGINT. Exiting...\n");
    running = 0;
}

void *convert_to_jpeg(void *arg) {
    ThreadArgs *args = (ThreadArgs *)arg;

    const uint32_t *src_pixel_ptr_base = (const uint32_t *)(args->src_fb_data + args->start_y * args->line_length);
    uint8_t *dest_pixel_ptr_base = args->dest_rgb888_data + args->start_y * args->width * 3;

    for (int y = args->start_y; y < args->end_y; y++) {
        const uint32_t *current_src_row = src_pixel_ptr_base;
        uint8_t *current_dest_row = dest_pixel_ptr_base;

        for (int x = 0; x < args->width; x++) {
            uint32_t pixel_value = current_src_row[x];

            // Mask off last 2 bits (alpha channel)
            *current_dest_row++ = (uint8_t)((pixel_value >> args->vinfo.red.offset) & 0xFF);
            *current_dest_row++ = (uint8_t)((pixel_value >> args->vinfo.green.offset) & 0xFF);
            *current_dest_row++ = (uint8_t)((pixel_value >> args->vinfo.blue.offset) & 0xFF);
        }
        // Advance pointers for the next row
        src_pixel_ptr_base = (const uint32_t*)((uint8_t*)src_pixel_ptr_base + args->line_length);
        dest_pixel_ptr_base += args->width * 3;
    }

    return NULL;
}

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

    signal(SIGINT, sigint_handler);

    int frame_count = 0;
    const long desired_frame_time_us = 1000000 / 30;

    while (running) {
        struct timeval start_time, end_time;
        gettimeofday(&start_time, NULL);

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

        int num_threads = sysconf(_SC_NPROCESSORS_ONLN);
        if (num_threads < 1) num_threads = 1;
        pthread_t threads[num_threads];
        ThreadArgs thread_args[num_threads];
        int rows_per_thread = fb_height / num_threads;
        int remaining_rows = fb_height % num_threads;

        for (int i = 0; i < num_threads; i++) {
            thread_args[i].src_fb_data = fb_data;
            thread_args[i].dest_rgb888_data = rgb888_buffer;
            thread_args[i].width = fb_width;
            thread_args[i].line_length = finfo.line_length;
            thread_args[i].vinfo = vinfo;

            thread_args[i].start_y = i * rows_per_thread;
            thread_args[i].end_y = (i + 1) * rows_per_thread;

            // Distribute rows
            if (i == num_threads - 1) {
                thread_args[i].end_y += remaining_rows;
            }

            if (pthread_create(&threads[i], NULL, convert_to_jpeg, (void *)&thread_args[i]) != 0) {
                perror("Error creating thread");
                free(rgb888_buffer);
                munmap(fb_data, fb_size);
                close(fbfd);
                return 1;
            }
        }

        for (int i = 0; i < num_threads; i++) {
            if (pthread_join(threads[i], NULL) != 0) {
                perror("Error joining thread");
                free(rgb888_buffer);
                munmap(fb_data, fb_size);
                close(fbfd);
                return 1;
            }
        }

        

        uint32_t *src_pixel_ptr = (uint32_t *)fb_data;
        uint8_t *dest_pixel_ptr = rgb888_buffer;

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

        gettimeofday(&end_time, NULL);
        long elapsed_us = (end_time.tv_sec - start_time.tv_sec) * 1000000 + (end_time.tv_usec - start_time.tv_usec);
        long sleep_us = desired_frame_time_us - elapsed_us;

        if (sleep_us > 0) {
            usleep(sleep_us);
        } else {
            // Frame processing took longer than desired frame time
            printf("Warning: Frame processing took too long (%ld us)!\n", elapsed_us);
        }

        jpeg_finish_compress(&cinfo);
        jpeg_destroy_compress(&cinfo);
        fclose(outfile);
    }


    free(rgb888_buffer);
    munmap(fb_data, fb_size);
    close(fbfd);

    return 0;
}