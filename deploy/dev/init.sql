CREATE
DATABASE `quanthub` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */ /*!80016 DEFAULT ENCRYPTION = 'N' */;

use
`quanthub`;

-- quanthub.quanthub_users definition
CREATE TABLE `quanthub_users`
(
    `id`           bigint                                                        NOT NULL AUTO_INCREMENT,
    `username`     varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `password`     varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `email`        varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `phone_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `role`         varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `created_at`   datetime                                                      DEFAULT NULL,
    `created_by`   varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `updated_at`   datetime                                                      DEFAULT NULL,
    `updated_by`   varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- quanthub.categories definition
CREATE TABLE `categories`
(
    `id`         bigint NOT NULL,
    `name`       varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `created_at` datetime                                                      DEFAULT NULL,
    `created_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `updated_at` datetime                                                      DEFAULT NULL,
    `updated_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- quanthub.articles definition
CREATE TABLE `articles`
(
    `id`           bigint NOT NULL,
    `author_id`    bigint NOT NULL,
    `title`        varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `sub_title`    varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `content`      text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
    `rate`         decimal(3, 1)                                                 DEFAULT NULL,
    `status`       varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `publish_date` date                                                          DEFAULT NULL,
    `created_at`   datetime                                                      DEFAULT NULL,
    `created_by`   varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `updated_at`   datetime                                                      DEFAULT NULL,
    `updated_by`   varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY            `articles_users_FK` (`author_id`),
    CONSTRAINT `articles_users_FK` FOREIGN KEY (`author_id`) REFERENCES `quanthub_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- quanthub.comments definition
CREATE TABLE `comments`
(
    `id`               bigint                                                        NOT NULL,
    `content`          text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
    `user_id`          bigint                                                        DEFAULT NULL,
    `publish_datetime` datetime                                                      DEFAULT NULL,
    `article_id`       bigint                                                        DEFAULT NULL,
    `status`           varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `created_at`       datetime                                                      DEFAULT NULL,
    `created_by`       varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `updated_at`       datetime                                                      DEFAULT NULL,
    `updated_by`       varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY                `comment_users_FK` (`user_id`),
    KEY                `comment_articles_FK` (`article_id`),
    CONSTRAINT `comment_articles_FK` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`),
    CONSTRAINT `comment_users_FK` FOREIGN KEY (`user_id`) REFERENCES `quanthub_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- quanthub.link_category_article definition
CREATE TABLE `link_category_article`
(
    `id`          bigint NOT NULL,
    `category_id` bigint                                                        DEFAULT NULL,
    `article_id`  bigint                                                        DEFAULT NULL,
    `created_at`  datetime                                                      DEFAULT NULL,
    `created_by`  varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `updated_at`  datetime                                                      DEFAULT NULL,
    `updated_by`  varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY           `link_category_article_articles_FK` (`article_id`),
    KEY           `link_category_article_categories_FK` (`category_id`),
    CONSTRAINT `link_category_article_articles_FK` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`),
    CONSTRAINT `link_category_article_categories_FK` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- quanthub.tags definition
CREATE TABLE `tags`
(
    `id`         bigint                                                        NOT NULL AUTO_INCREMENT,
    `name`       varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `created_at` datetime                                                      DEFAULT NULL,
    `created_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `updated_at` datetime                                                      DEFAULT NULL,
    `updated_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- quanthub.link_tag_article definition
CREATE TABLE `link_tag_article`
(
    `id`         bigint NOT NULL AUTO_INCREMENT,
    `article_id` bigint NOT NULL,
    `tag_id`     bigint NOT NULL,
    `created_at` datetime                                                      DEFAULT NULL,
    `created_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    `updated_at` datetime                                                      DEFAULT NULL,
    `updated_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY          `link_tag_article_articles_FK` (`article_id`),
    KEY          `link_tag_article_tags_FK` (`tag_id`),
    CONSTRAINT `link_tag_article_articles_FK` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`),
    CONSTRAINT `link_tag_article_tags_FK` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- quanthub.likes definition
CREATE TABLE `likes`
(
    `id`         bigint NOT NULL AUTO_INCREMENT,
    `user_id`    bigint NOT NULL,
    `article_id` bigint NOT NULL,
    `created_at` datetime                                DEFAULT NULL,
    `created_by` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
    `updated_at` datetime                                DEFAULT NULL,
    `updated_by` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY          `likes_articles_FK` (`article_id`),
    KEY          `likes_quanthub_users_FK` (`user_id`),
    CONSTRAINT `likes_articles_FK` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`),
    CONSTRAINT `likes_quanthub_users_FK` FOREIGN KEY (`user_id`) REFERENCES `quanthub_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
