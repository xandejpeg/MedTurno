#!/bin/bash
tail -n 0 -f /var/www/doctorturn/storage/logs/laravel.log | grep --line-buffered -iE 'escala|whatsapp|publicad|mail|notif|job|queue|error|exception|graph'
