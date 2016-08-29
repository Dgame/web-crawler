mod crawler;

use std::fs::File;
use std::io::Read;
use std::io::Write;

use crawler::Crawler;
use crawler::debug::*;

fn resume(urls: &mut Vec<String>) {
    let mut file = match File::open("shutdown.txt") {
        Err(_) => {}
        Ok(mut file) => {
            let mut content = String::new();
            match file.read_to_string(&mut content) {
                Err(_) => {}
                Ok(_) => {
                    file.write_all(b"");
                    let last_found_urls: Vec<String> = content.lines()
                        .map(|s| s.trim())
                        .filter(|s| !s.is_empty())
                        .map(|s| String::from(s))
                        .collect();
                    urls.extend_from_slice(&last_found_urls);
                }
            }
        }
    };
}

fn main() {
    let crawler = Crawler::new(Debug::new(DEBUG_NONE));
    let mut urls: Vec<String> = Vec::new();
    resume(&mut urls);

    urls.push(String::from("http://web.de/"));
    urls.push(String::from("http://gmx.de/"));
    urls.push(String::from("http://www.wikipedia.org/"));
    urls.push(String::from("http://www.zeit.de/news/index/"));
    urls.push(String::from("http://www.t-online.de/nachrichten/"));
    urls.push(String::from("http://www.focus.de/"));
    urls.push(String::from("http://www.n-tv.de/"));
    urls.push(String::from("http://www.weltderwunder.de/"));
    urls.push(String::from("http://www.zdnet.de/"));
    urls.push(String::from("http://www.it-business.de/"));
    urls.push(String::from("http://www.cnet.com/news/"));
    urls.push(String::from("http://www.pcwelt.de/"));
    urls.push(String::from("http://www.winfuture.de/"));
    urls.push(String::from("http://www.it-times.de/"));
    urls.push(String::from("http://www.heise.de/"));
    urls.push(String::from("http://www.golem.de/"));
    urls.push(String::from("http://www.reddit.com/"));
    urls.push(String::from("http://www.spieleprogrammierer.de/"));
    urls.push(String::from("http://www.dlang.org/"));
    crawler.dispatch(urls);
}
