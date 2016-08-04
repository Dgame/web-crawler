#[allow(dead_code)]
const LOG_NONE: u8 = 0x0;
const LOG_SPAWN: u8 = 0x1;
const LOG_URL: u8 = 0x2;
const LOG_STATUS: u8 = 0x4;
const LOG_OUTPUT: u8 = 0x8;
#[allow(dead_code)]
const LOG_ALL: u8 = LOG_SPAWN | LOG_URL | LOG_STATUS | LOG_OUTPUT;
const LOG_LEVEL: u8 = LOG_NONE;
const THREAD_NUM: usize = 8;

fn spawn(links: Vec<String>) {
    if LOG_LEVEL & LOG_SPAWN != 0 {
        println!("Spawn: {:?}", &links);
    }

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

    if LOG_LEVEL & LOG_URL != 0 {
        println!("Crawl URL : {}", url);
    }

    let output = Command::new("php").arg("crawl.php").arg(&url).output().unwrap();
    if LOG_LEVEL & LOG_STATUS != 0 {
        println!("status: {}", &output.status);
    }

    let output = String::from_utf8_lossy(&output.stdout);
    if LOG_LEVEL & LOG_OUTPUT != 0 {
        println!("output: {}", &output);
    }

    let links: Vec<String> = output.split("\n")
        .map(|s| s.trim())
        .filter(|s| !s.is_empty())
        .map(|s| String::from(s))
        .collect();

    spawn(links);
}

fn main() {
    spawn(vec![String::from("http://web.de/"),
               String::from("http://gmx.de/"),
               String::from("http://www.wikipedia.org/"),
               String::from("http://www.zeit.de/news/index/"),
               String::from("http://www.t-online.de/nachrichten/"),
               String::from("http://www.focus.de/"),
               String::from("http://www.n-tv.de/"),
               String::from("http://www.weltderwunder.de/"),
               String::from("http://www.zdnet.de/"),
               String::from("http://www.it-business.de/"),
               String::from("http://www.cnet.com/news/"),
               String::from("http://www.pcwelt.de/"),
               String::from("http://www.winfuture.de/"),
               String::from("http://www.it-times.de/"),
               String::from("http://www.heise.de/"),
               String::from("http://www.golem.de/"),
               String::from("http://www.reddit.com/"),
               String::from("http://www.spieleprogrammierer.de/"),
               String::from("http://www.dlang.org/")]);
}
