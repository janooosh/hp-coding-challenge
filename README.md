This coding challenge has been used to challenge new devs for Hello Pine. 
It contains an excerpt of a production feature of Hello Pine - merging products.
Please see the **solution** branch for an example solution.

Candidates have been supplied with the following instructions:
https://docs.google.com/document/d/1ZmrOOIGs8QftZiQGEPYsFrrFi6DrTQfiekErcSzPj44

# Product Merger Challenge

Welcome and thank you for accepting the challenge 🚀

## System Requirements
- Docker
- PostgreSQL
- PHP 8.2
- Composer

## Setup
- Clone the repsitory
- Install the composer dependencies
- Copy .env.example to .env
- Run ./vendor/bin/sail up
- From inside the docker container, run php artisan migrate and php artisan key:generate
- Run npm install && npm run dev (outside of the container)

## Running the test
- From inside the docker container, run php artisan test

## Submission
Please **do not** push to this repository or create a PR. Please push your changes to a **new, private** repository and invite jan@hello-pine.com as collaborateur.
