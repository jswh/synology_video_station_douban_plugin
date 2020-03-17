addEventListener('fetch', event => {
    event.respondWith(handleRequest(event.request))
})

async function handleRequest(request) {
    targetUrl = request.url.split('-----')[1]
    request.url = targetUrl
    let response = await fetch(request)
    return response
}
