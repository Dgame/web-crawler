function(pageId, values) {
    var pr = 0,
        out = [], 
        out_count = 0,
        diff = 0,
        prev_pr = 0,
        total = 0,
        url = "";

    for (var i in values) {
        if (!!values[i].url) {
            url = values[i].url;
        }
        prevOut = values[i].out
        for (var key in prevOut) {
            out[key] = prevOut[key];
        }
        out_count += values[i].out_count;
        pr += values[i].pr;
        prev_pr += values[i].prev_pr;
        total += values[i].total;
    }

    pr = 0.15 / total + 0.85 * pr;

    diff = Math.abs(prev_pr - pr);

    return {
        "total" : total, 
        "pr" : pr, 
        "out" : out, 
        "out_count": out_count,
        "diff": diff,
        "prev_pr": prev_pr,
        "url": url
    };
}
