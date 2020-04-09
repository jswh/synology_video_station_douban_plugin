addEventListener('fetch', event => {
    event.respondWith(handleRequest(event.request))
})

async function handleRequest(request) {
    targetUrl = request.url.split('-----')[1]

    return await fetch(targetUrl)
}
