function(pageId, values) {
    var pr = 0
        , diff = 0
        , ps = {} 
        , prevpr = 0 
        , beta = 0.9
        , total= 0;

    for (var i in values) {
        var prevPS = values[i]["ps"];
        for (var key in prevPS) {
            ps[key] = prevPS[key];
        }
        prevpr += values[i]["prevpr"];
        pr += values[i]["pr"];
        total += values[i]["total"];
    }

    if (prevpr != 0) {
        diff = Math.abs(prevpr - pr) / prevpr;
    }

    return {"total" : total
        , "pr" : pr 
        , "ps" : ps
        , "diff": diff
        , "prevpr" : prevpr};
}
