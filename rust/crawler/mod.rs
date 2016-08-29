pub mod debug;

//extern crate crossbeam;
use std::thread;
use crawler::debug::Debug;

const THREAD_NUM: usize = 8;

#[derive(Clone)]
pub struct Crawler {
    debug: Debug,
}

impl Crawler {
    pub fn new(debug: Debug) -> Crawler {
        Crawler { debug: debug }
    }

    pub fn dispatch(&self, links: Vec<String>) {
        self.debug.debug_spawn(format!("Spawn: {:?}", &links));

        for chunk in links.chunks(THREAD_NUM) {
            self.spawn(chunk.to_vec());
        }
    }

    fn spawn(&self, chunk: Vec<String>) {
        let mut threads = Vec::new();
        for url in chunk {
            let c = Crawler::new(self.debug.clone());
            threads.push(thread::spawn(move || c.crawl(&url)));
        }

        for t in threads {
            let _ = t.join();
        }
    }

    pub fn crawl(&self, url: &str) {
        use std::process::Command;

        self.debug.debug_url(format!("Crawl URL : {}", &url));
        let output = Command::new("php").current_dir("../")
                                        .arg("crawl.php")
                                        .arg(&url)
                                        .output();
        if let Ok(output) = output {
            self.debug.debug_status(format!("status: {}", &output.status));

            let output = String::from_utf8_lossy(&output.stdout);
            self.debug.debug_output(format!("output: {}", &output));

            let links: Vec<String> = output.lines()
                                           .map(|s| s.trim())
                                           .filter(|s| !s.is_empty())
                                           .map(|s| String::from(s))
                                           .collect();

            self.dispatch(links);
        }
    }
}