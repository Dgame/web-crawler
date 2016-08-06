function() {
    var pr = 0;
    if (this.value.out_count > 0) {
        pr = (this.value.pr / this.value.out_count) * 100;
    }

    for (var i in this.value.out) {
        emit(
            new ObjectId(this.value.out[i]), 
            {
                total: 0, 
                pr: pr, 
                out: [], 
                out_count: 0,
                diff: 0.0,
                prev_pr: 0.0,
                url: ""
            }
        );
    }

    emit(
        this._id, 
        {
            total: this.value.total, 
            pr: 0.0, 
            out : this.value.out, 
            out_count: this.value.out_count,
            diff: this.value.diff,
            prev_pr: this.value.pr,
            url: this.value.url
        }
    );

    emit(
        this._id, 
        {
            total: 0.0, 
            pr : 0.0,
            out : [],
            out_count: 0,
            diff : 0.0,
            prev_pr : 0.0,
            url: ""
        }
    );
}
