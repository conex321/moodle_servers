FROM php:8.3-apache

# System dependencies for PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libicu-dev \
    libxml2-dev \
    libzip-dev \
    libsodium-dev \
    libonig-dev \
    libcurl4-openssl-dev \
    libldap2-dev \
    unzip \
    wget \
    cron \
    && rm -rf /var/lib/apt/lists/*

# Configure and install PHP extensions required by Moodle 5.1.3
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        curl \
        gd \
        intl \
        mbstring \
        soap \
        sodium \
        zip \
        exif \
        opcache \
        mysqli \
        pdo_mysql \
    && pecl install igbinary redis \
    && docker-php-ext-enable igbinary redis

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Download and extract Moodle 5.1.3
RUN wget -q https://download.moodle.org/download.php/direct/stable501/moodle-5.1.3.tgz -O /tmp/moodle.tgz \
    && tar -xzf /tmp/moodle.tgz -C /tmp \
    && rm -rf /var/www/html/* \
    && mv /tmp/moodle/* /var/www/html/ \
    && rm -rf /tmp/moodle /tmp/moodle.tgz

# Install Edwiser RemUI theme (into both root and public theme dirs for Moodle 5.x)
COPY moodleplugins/Edwiser-RemUI-v5.1.2.zip /tmp/remui-outer.zip
RUN cd /tmp && unzip -q remui-outer.zip \
    && unzip -q theme_remui.zip -d /var/www/html/theme/ \
    && cp -a /var/www/html/theme/remui /var/www/html/public/theme/remui \
    && rm -f /tmp/remui-outer.zip /tmp/theme_remui.zip

# Install Edwiser Advanced Block (required by Page Builder to render page content)
COPY moodleplugins/block_edwiseradvancedblock.zip /tmp/block_edwiseradvancedblock.zip
RUN cd /tmp && unzip -q block_edwiseradvancedblock.zip \
    && mv edwiseradvancedblock /var/www/html/public/blocks/edwiseradvancedblock \
    && rm -f /tmp/block_edwiseradvancedblock.zip
COPY patches/edwiseradvancedblock_lib.php /var/www/html/public/blocks/edwiseradvancedblock/lib.php

# Install Edwiser Page Builder Filter (required by Page Builder for content rendering)
COPY moodleplugins/filter_edwiserpbf.zip /tmp/filter_edwiserpbf.zip
RUN cd /tmp && unzip -q filter_edwiserpbf.zip \
    && mv edwiserpbf /var/www/html/public/filter/edwiserpbf \
    && rm -f /tmp/filter_edwiserpbf.zip

# ============================================================
# Ghost-fix plugins (added 2026-04-19)
#
# These plugins were registered in mdl_config_plugins and
# mdl_external_functions on the source snapshot but had no code
# on disk in this image. The result was a coding_exception on
# admin/webservice/service_functions.php that blocked adding any
# WS function to an external service. See DEPLOYMENT.md section
# on this incident. Install order below matches the known-good
# sequence used to recover the live container on 2026-04-19.
# ============================================================

# Standalone: block_edwiser_site_monitor — zip's inner dir is 'sitemonitor'
# but version.php's $plugin->component is 'block_edwiser_site_monitor', so
# the on-disk directory must be blocks/edwiser_site_monitor/.
COPY moodleplugins/block_site_monitor.zip /tmp/block_site_monitor.zip
RUN cd /tmp && unzip -oq block_site_monitor.zip \
    && mv sitemonitor /var/www/html/public/blocks/edwiser_site_monitor \
    && rm -f /tmp/block_site_monitor.zip

# Standalone: mod_edwiservideoactivity
COPY moodleplugins/edwiservideoactivity.zip /tmp/edwiservideoactivity.zip
RUN cd /tmp && unzip -oq edwiservideoactivity.zip \
    && mv edwiservideoactivity /var/www/html/public/mod/edwiservideoactivity \
    && rm -f /tmp/edwiservideoactivity.zip

# Standalone: format_edwiservideoformat (depends on mod_edwiservideoactivity)
COPY moodleplugins/edwiservideoformat.zip /tmp/edwiservideoformat.zip
RUN cd /tmp && unzip -oq edwiservideoformat.zip \
    && mv edwiservideoformat /var/www/html/public/course/format/edwiservideoformat \
    && rm -f /tmp/edwiservideoformat.zip

# Standalone: local_edwiserreports — zip's inner dir is 'moodle-local-edwiserreports',
# rename to local/edwiserreports to match $plugin->component.
COPY moodleplugins/moodle-local-edwiserreports-1.zip /tmp/local-edwiserreports.zip
RUN cd /tmp && unzip -oq local-edwiserreports.zip \
    && mv moodle-local-edwiserreports /var/www/html/public/local/edwiserreports \
    && rm -f /tmp/local-edwiserreports.zip

# Edwiser RemUI bundle — re-unzip to extract the 4 inner plugins we need
# beyond theme_remui (which was already installed above):
#   block_edwiserratingreview, local_edwisersiteimporter, local_edwiserpagebuilder, local_sitesync.
COPY moodleplugins/Edwiser-RemUI-v5.1.2.zip /tmp/remui-bundle.zip
RUN cd /tmp && unzip -oq remui-bundle.zip \
    && WORK=$(mktemp -d) && cd "$WORK" \
    && unzip -oq /tmp/Plugins/block_edwiserratingreview.zip \
    && mv edwiserratingreview /var/www/html/public/blocks/edwiserratingreview \
    && cd / && rm -rf "$WORK" && WORK=$(mktemp -d) && cd "$WORK" \
    && unzip -oq /tmp/Plugins/local_edwisersiteimporter.zip \
    && mv edwisersiteimporter /var/www/html/public/local/edwisersiteimporter \
    && cd / && rm -rf "$WORK" && WORK=$(mktemp -d) && cd "$WORK" \
    && unzip -oq "/tmp/Plugins/Page Builder Plugins/local_edwiserpagebuilder.zip" \
    && mv edwiserpagebuilder /var/www/html/public/local/edwiserpagebuilder \
    && cd / && rm -rf "$WORK" && WORK=$(mktemp -d) && cd "$WORK" \
    && unzip -oq "/tmp/Plugins/Site Sync Plugin (Experimental)/local_sitesync.zip" \
    && mv local_sitesync /var/www/html/public/local/sitesync \
    && cd / && rm -rf "$WORK" /tmp/remui-bundle.zip /tmp/Plugins /tmp/Installation*

# Edwiser Forms-Pro bundle — extract 3 inner plugins.
COPY moodleplugins/Edwiser-Forms-Pro.zip /tmp/forms-pro.zip
RUN cd /tmp && unzip -oq forms-pro.zip \
    && WORK=$(mktemp -d) && cd "$WORK" \
    && unzip -oq /tmp/Edwiser-Forms-Pro/filter_edwiserformlink.zip \
    && mv edwiserformlink /var/www/html/public/filter/edwiserformlink \
    && cd / && rm -rf "$WORK" && WORK=$(mktemp -d) && cd "$WORK" \
    && unzip -oq /tmp/Edwiser-Forms-Pro/local_edwiserform.zip \
    && mv local_edwiserform /var/www/html/public/local/edwiserform \
    && cd / && rm -rf "$WORK" && WORK=$(mktemp -d) && cd "$WORK" \
    && unzip -oq /tmp/Edwiser-Forms-Pro/mod_edwiserform.zip \
    && mv mod_edwiserform /var/www/html/public/mod/edwiserform \
    && cd / && rm -rf "$WORK" /tmp/forms-pro.zip /tmp/Edwiser-Forms-Pro

# Install Interactive Video plugin (required for curriculum deployment)
# This plugin provides the mod_interactivevideo activity module used by
# deploy_to_moodle.php to create interactive video lessons with Vimeo
# chapters and embedded quiz questions.
#
# Source archive: moodleplugins/mod_interactivevideo.zip
# Update procedure: replace the zip with a newer tagged release, confirm the
# top-level directory name below, then rebuild this image.
COPY moodleplugins/mod_interactivevideo.zip /tmp/mod_interactivevideo.zip
RUN cd /tmp && unzip -oq mod_interactivevideo.zip \
    && mv moodle-mod_interactivevideo-main /var/www/html/public/mod/interactivevideo \
    && rm -f /tmp/mod_interactivevideo.zip

# Moodle 5.x serves from public/ — update Apache DocumentRoot
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' \
    /etc/apache2/sites-available/000-default.conf \
    /etc/apache2/apache2.conf

# Create moodledata directory
RUN mkdir -p /var/moodledata \
    && chown -R www-data:www-data /var/moodledata \
    && chmod -R 777 /var/moodledata

# Fix PHP warning in choicelist.php (Moodle 5.1.3 bug: undefined "none" key)
COPY patches/choicelist_fixed.php /var/www/html/public/lib/classes/output/choicelist.php


# Fix bare $ jQuery references that throw 'TypeError: $ is not a function'
# (RemUI / Edwiser bundled JS uses bare $ outside require(['jquery']) wrappers)
COPY patches/layout_require_js.mustache /var/www/html/public/local/edwiserpagebuilder/templates/layout_require_js.mustache
COPY patches/login.mustache /var/www/html/public/theme/remui/templates/login.mustache
COPY patches/edwiseradvancedblock_blockcontent.mustache /var/www/html/public/blocks/edwiseradvancedblock/templates/blockcontent.mustache
COPY patches/theme_remui_bs4-compat.min.js /var/www/html/public/theme/remui/amd/build/bs4-compat.min.js
COPY patches/theme_remui_bs4-compat.js /var/www/html/public/theme/remui/amd/src/bs4-compat.js

# Set ownership on web root
RUN chown -R www-data:www-data /var/www/html

# Copy custom php.ini
COPY php.ini /usr/local/etc/php/conf.d/moodle.ini

# Copy config.php into the Moodle root (public/config.php loads ../config.php)
COPY config.php /var/www/html/config.php

# Copy entrypoint
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
