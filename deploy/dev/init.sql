CREATE
DATABASE `quanthub` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */ /*!80016 DEFAULT ENCRYPTION = 'N' */;

use
`quanthub`;

-- quanthub.quanthub_users definition
CREATE TABLE `quanthub_users`
(
    `id`           bigint unsigned NOT NULL AUTO_INCREMENT,
    `auth0_id`     varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `username`     varchar(100) COLLATE utf8mb4_general_ci                       NOT NULL,
    `password`     varchar(100) COLLATE utf8mb4_general_ci                       DEFAULT NULL,
    `email`        varchar(100) COLLATE utf8mb4_general_ci                       DEFAULT NULL,
    `phone_number` varchar(100) COLLATE utf8mb4_general_ci                       DEFAULT NULL,
    `role`         varchar(100) COLLATE utf8mb4_general_ci                       DEFAULT NULL,
    `avatar_link`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `created_by`   varchar(100) COLLATE utf8mb4_general_ci                       DEFAULT NULL,
    `updated_by`   varchar(100) COLLATE utf8mb4_general_ci                       DEFAULT NULL,
    `created_at`   timestamp NULL DEFAULT NULL,
    `updated_at`   timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `quanthub_users_auth0_id_unique` (`auth0_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- quanthub.categories definition
CREATE TABLE `categories`
(
    `id`         bigint unsigned NOT NULL AUTO_INCREMENT,
    `name`       varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `created_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `updated_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- quanthub.articles definition
CREATE TABLE `articles`
(
    `id`                 bigint unsigned NOT NULL AUTO_INCREMENT,
    `author_id`          bigint unsigned NOT NULL,
    `title`              varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `sub_title`          varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `content`            text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `category_id`        bigint unsigned DEFAULT NULL,
    `rate`               decimal(3, 1)                                                 DEFAULT NULL,
    `status`             varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `type`               varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `draft_reference_id` bigint unsigned DEFAULT NULL,
    `cover_image_link`   varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `publish_date`       date                                                          DEFAULT NULL,
    `attachment_link`    varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `attachment_name`    varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `created_by`         varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `updated_by`         varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `created_at`         timestamp NULL DEFAULT NULL,
    `updated_at`         timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY                  `articles_author_id_foreign` (`author_id`),
    KEY                  `articles_category_id_foreign` (`category_id`),
    CONSTRAINT `articles_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `quanthub_users` (`id`),
    CONSTRAINT `articles_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- quanthub.comments definition
CREATE TABLE `comments`
(
    `id`               bigint unsigned NOT NULL AUTO_INCREMENT,
    `content`          text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
    `user_id`          bigint unsigned DEFAULT NULL,
    `publish_datetime` datetime                                                      DEFAULT NULL,
    `article_id`       bigint unsigned DEFAULT NULL,
    `status`           varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `created_by`       varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `updated_by`       varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `created_at`       timestamp NULL DEFAULT NULL,
    `updated_at`       timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY                `comments_user_id_foreign` (`user_id`),
    KEY                `comments_article_id_foreign` (`article_id`),
    CONSTRAINT `comments_article_id_foreign` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `quanthub_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- quanthub.tags definition
CREATE TABLE `tags`
(
    `id`         bigint unsigned NOT NULL AUTO_INCREMENT,
    `name`       varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `created_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `updated_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- quanthub.link_tag_articles definition
CREATE TABLE `link_tag_articles`
(
    `id`         bigint unsigned NOT NULL AUTO_INCREMENT,
    `article_id` bigint unsigned NOT NULL,
    `tag_id`     bigint unsigned NOT NULL,
    `created_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `updated_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY          `link_tag_article_article_id_foreign` (`article_id`),
    KEY          `link_tag_article_tag_id_foreign` (`tag_id`),
    CONSTRAINT `link_tag_article_article_id_foreign` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `link_tag_article_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- quanthub.likes definition
CREATE TABLE `likes`
(
    `id`         bigint unsigned NOT NULL AUTO_INCREMENT,
    `user_id`    bigint unsigned NOT NULL,
    `article_id` bigint unsigned NOT NULL,
    `type`       tinyint(1) NOT NULL DEFAULT '1',
    `created_by` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
    `updated_by` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY          `likes_user_id_foreign` (`user_id`),
    KEY          `likes_article_id_foreign` (`article_id`),
    CONSTRAINT `likes_article_id_foreign` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `likes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `quanthub_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
