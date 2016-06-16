const THREAD_NUM: usize = 8;

fn spawn(links: Vec<String>) {
    for chunk in links.chunks(THREAD_NUM) {
        spawn_chunk(chunk.to_vec());
    }
}

fn spawn_chunk(chunk: Vec<String>) {
    use std::thread;

    let mut threads = vec![];

    for url in chunk {
        threads.push(thread::spawn(move || crawl(&url)));
    }

    for child in threads {
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
        .unwrap_or_else(|e| panic!("failed to execute process: {}", e));

    println!("status: {}", output.status);

    let output = String::from_utf8_lossy(&output.stdout);
    let links: Vec<String> = output.split("\n")
        .map(|s| s.trim())
        .filter(|s| !s.is_empty())
        .map(|s| String::from(s))
        .collect();

    println!("Found: {:?}", &links);
    println!("====");

    spawn(links);
}

fn main() {
    spawn(vec![String::from("http://web.de"),
               String::from("http://heise.de"),
               String::from("http://golem.de"),
               String::from("http://reddit.com"),
               String::from("http://spieleprogrammierer.de"),
               String::from("http://forum.dlang.org")]);
}
