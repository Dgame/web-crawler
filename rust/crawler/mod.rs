pub mod debug;

extern crate crossbeam;

use crawler::debug::Debug;

const THREAD_NUM: usize = 8;
const CHUNK_SIZE: usize = THREAD_NUM;

pub struct Crawler {
    debug: Debug,
}

impl Crawler {
    pub fn new(debug: Debug) -> Crawler {
        Crawler { debug: debug }
    }

    pub fn spawn(&self, links: Vec<String>) {
        self.debug.debug_spawn(format!("Spawn: {:?}", &links));

        for chunk in links.chunks(CHUNK_SIZE) {
            self.spawn_chunk(chunk.to_vec());
        }
    }

    fn spawn_chunk(&self, chunk: Vec<String>) {
        let mut threads = vec![];
        crossbeam::scope(|scope| {
            threads.push(scope.spawn(move || self.crawl(chunk)));
        });
    }

    fn crawl(&self, chunk: Vec<String>) {
        use std::process::Command;

        self.debug.debug_url(format!("Crawl: {:?}", &chunk));
        let output = Command::new("php")
            .current_dir("../")
            .arg("crawl.php")
            .arg(chunk.join(","))
            .output()
            .unwrap();
        self.debug.debug_status(format!("status: {}", &output.status));

        let output = String::from_utf8_lossy(&output.stdout);
        self.debug.debug_output(format!("output: {}", &output));

        let links: Vec<String> = output.split("\n")
            .map(|s| s.trim())
            .filter(|s| !s.is_empty())
            .map(|s| String::from(s))
            .collect();

        self.spawn(links);
    }
}