# Contributing


## Internal Dashboard

Run the following commands:

`npm install && npm run dev`
In the `vite.config.ts` file, a proxy is set up to redirect all API requests to `http://127.0.0.1:4040`. To work with real data while in development mode, you can open a tunnel using Cloakr on the actual server, which will direct the dashboard API to the `4040` port. (Please note, the WebSocket connection doesn't work in this setup, so you'll need to refresh the page to see new requests.)