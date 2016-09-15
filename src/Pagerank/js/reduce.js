function(pageId, values) {
    var pr = 0.0,
        out = [], 
        out_count = 0,
        diff = 0.0,
        prev_pr = 0.0;

    for (var i in values) {
        prevOut = values[i].out
        for (var key in prevOut) {
            out[key] = prevOut[key];
        }
        out_count += values[i].out_count;
        pr += values[i].pr;
        prev_pr += values[i].prev_pr;
    }

    pr = 0.15 + 0.85 * pr;

    diff = Math.abs(prev_pr - pr);

    return {
        "pr" : pr, 
        "out" : out, 
        "out_count": out_count,
        "diff": diff,
        "prev_pr": prev_pr
    };
}
