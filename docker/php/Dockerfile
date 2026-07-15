# syntax=docker/dockerfile:1

ARG PHP_IMAGE=ghcr.io/sylius/sylius-php:8.3-alpine

FROM ${PHP_IMAGE}

USER root

RUN apk add --no-cache bash

ARG PHP_RUNTIME_USER=root

USER ${PHP_RUNTIME_USER}
