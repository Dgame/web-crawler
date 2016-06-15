fn spawn(links: Vec<String>) {
    use std::cmp::min;

    let thread_num = min(links.len(), 8);

    for _ in 0..thread_num {
        for chunk in links.chunks(thread_num) {
            spawn_chunk(chunk.to_vec());
        }
    }
}

fn spawn_chunk(chunk: Vec<String>) {
    use std::thread;

    let mut threads = vec![];

    for url in chunk {
        if !url.is_empty() {
            threads.push(thread::spawn(move || {
                crawl(&url)
            }));
        }
    }

    for child in threads {
        // Wait for the thread to finish. Returns a result.
        let _ = child.join();
    }
}

fn crawl(url: &str) {
     use std::process::Command;

     println!("Crawl URL : {}", url);

    let output = Command::new("php")
                        .arg("crawl.php")
                        .arg(&url)
                        .output()
                        .unwrap_or_else(|e| { panic!("failed to execute process: {}", e) });

    println!("status: {}", output.status);

    let output = String::from_utf8_lossy(&output.stdout);
    let links = output.split("\n").map(|s| String::from(s)).collect();
    for url in &links {
        println!("Link: {}", url);
    }

    spawn(links);
}

fn main() {
    spawn(vec![
        String::from("http://web.de"),
        String::from("http://heise.de"),
        String::from("http://golem.de"),
        String::from("http://reddit.com"),
        String::from("http://spieleprogrammierer.de"),
        String::from("http://forum.dlang.org")
    ]);
}