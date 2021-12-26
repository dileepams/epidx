let mic = document.getElementById("mic");
let chatareamain = document.querySelector('.chatarea-main');
let chatareaouter = document.querySelector('.chatarea-outer');

let hello = [
    "Hello there",
    "Hi there",
    "Hey!!",
];

let HowAreYou = [
    "I am really good. what about you?",
    "I am good. How about you?",
    "i am good you little piece of love", 
    "i am fine, what about you",
];

let help = [
    "How may i assist you?",
    "How can i help you?",
    "What i can do for you?"
];

let WhoAreYou = [
    "I am epid x Assistant. I love to talk with humans", 
    "I am your personal Assistant. I love to make friends like you",
    "I am epid x Assistant. I love help to humans"
];

let products = [
    'epid x Shop has at least all kind of products you need in a pendemic such as face mask, sanitizer, glouse and more <br> <a href="http://localhost/epidx/shop/" target="_blank" style="color:#fff;">Go to shop</a>',
];

let buyProducts = [
    "Yes, sure.",
    "Yes you can",
    "Sure, you can buy",
    "Yes we have"
];

let thank = [
    "Most welcome",
    "Not an issue",
    "Its my pleasure",
    "Mention not"
];
let closing = [
    'Ok bye-bye',
    'Bye take-care',
    'Bye-bye, see you soon..'
]


let wrong = [
    "Sorry! Did not get it. Still I am learning humans' languages.",
    "Could not understand. Still I am learning humans' languages.",
    "Sorry!! Could not get it",
    "Can't understand. I am still learning humans' languages.",
]


const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
const recognition = new SpeechRecognition();

function showusermsg(usermsg){
    let output = '';
    output += `<div class="chatarea-inner user">${usermsg}</div>`;
    chatareaouter.innerHTML += output;
    return chatareaouter;
}

function showchatbotmsg(chatbotmsg){
    let output = '';
    output += `<div class="chatarea-inner chatbot">${chatbotmsg}</div>`;
    chatareaouter.innerHTML += output;
    return chatareaouter;
}


function chatbotvoice(message){
    const speech = new SpeechSynthesisUtterance();
    //speech.text = "Sorry! Did not get it. Still I am learning humans' languages.";
    let finalresult = wrong[Math.floor(Math.random() * wrong.length)];
    speech.text = finalresult;

    if(message.includes('hello')){
        let finalresult = hello[Math.floor(Math.random() * hello.length)];
        speech.text = finalresult;
        //window.open('https://www.google.com/search?q=' + message);
    }
    if(message.includes('Hello')){
        let finalresult = hello[Math.floor(Math.random() * hello.length)];
        speech.text = finalresult;
        //window.open('https://www.google.com/search?q=' + message);
    }

    if(message.includes('how are you')){
        let finalresult = HowAreYou[Math.floor(Math.random() * HowAreYou.length)];
        speech.text = finalresult;
    }
    if(message.includes('How are you')){
        let finalresult = HowAreYou[Math.floor(Math.random() * HowAreYou.length)];
        speech.text = finalresult;
    }

    if(message.includes('who are you')){
        let finalresult = WhoAreYou[Math.floor(Math.random() * WhoAreYou.length)];
        speech.text = finalresult;
    }
    if(message.includes('Who are you')){
        let finalresult = WhoAreYou[Math.floor(Math.random() * WhoAreYou.length)];
        speech.text = finalresult;
    }

    if(message.includes('about you')){
        let finalresult = WhoAreYou[Math.floor(Math.random() * WhoAreYou.length)];
        speech.text = finalresult;
    }

    if(message.includes('fine')){
        let finalresult = help[Math.floor(Math.random() * help.length)];
        speech.text = finalresult;
    }
    if(message.includes('not bad')){
        let finalresult = help[Math.floor(Math.random() * help.length)];
        speech.text = finalresult;
    }
    if(message.includes('Not bad')){
        let finalresult = help[Math.floor(Math.random() * help.length)];
        speech.text = finalresult;
    }
    if(message.includes('I am good')){
        let finalresult = help[Math.floor(Math.random() * help.length)];
        speech.text = finalresult;
    }

    
//buy
    if(message.includes('products')){
        let finalresult = products[Math.floor(Math.random() * products.length)];
        speech.text = finalresult;
    }

    if(message.includes('n95')){
        let finalresult = n95mask[Math.floor(Math.random() * n95mask.length)];
        speech.text = finalresult;
        window.open('http://localhost/epidx/shop/product/niosh-approved-n95-mask-particulate-respirator/');
    }
    if(message.includes('N95')){
        let finalresult = buyProducts[Math.floor(Math.random() * buyProducts.length)];
        speech.text = finalresult;
        window.open('http://localhost/epidx/shop/product/niosh-approved-n95-mask-particulate-respirator/');
    }
    if(message.includes('N 95')){
        let finalresult = buyProducts[Math.floor(Math.random() * buyProducts.length)];
        speech.text = finalresult;
        window.open('http://localhost/epidx/shop/product/niosh-approved-n95-mask-particulate-respirator/');
    }

    if(message.includes('K-95')){
        let finalresult = buyProducts[Math.floor(Math.random() * buyProducts.length)];
        speech.text = finalresult;
        window.open('http://localhost/epidx/shop/product/niosh-approved-n95-mask-particulate-respirator/');
    }

    if(message.includes('black face mask')){
        let finalresult = buyProducts[Math.floor(Math.random() * buyProducts.length)];
        speech.text = finalresult;
        window.open('http://localhost/epidx/shop/product/respirone-nano-av99-mask/');
    }

    if(message.includes('a v 99')){
        let finalresult = buyProducts[Math.floor(Math.random() * buyProducts.length)];
        speech.text = finalresult;
        window.open('http://localhost/epidx/shop/product/respirone-nano-av99-mask/');
    }
    if(message.includes('AV 99')){
        let finalresult = buyProducts[Math.floor(Math.random() * buyProducts.length)];
        speech.text = finalresult;
        window.open('http://localhost/epidx/shop/product/respirone-nano-av99-mask/');
    }
    if(message.includes('V99')){
        let finalresult = buyProducts[Math.floor(Math.random() * buyProducts.length)];
        speech.text = finalresult;
        window.open('http://localhost/epidx/shop/product/respirone-nano-av99-mask/');
    }

    if(message.includes('kids face mask')){
        let finalresult = buyProducts[Math.floor(Math.random() * buyProducts.length)];
        speech.text = finalresult;
        window.open('http://localhost/epidx/shop/product/kiddies-face-masks/');
    }
    if(message.includes('face mask for kids')){
        let finalresult = buyProducts[Math.floor(Math.random() * buyProducts.length)];
        speech.text = finalresult;
        window.open('http://localhost/epidx/shop/product/kiddies-face-masks/');
    }

    if(message.includes('surgical mask')){
        let finalresult = buyProducts[Math.floor(Math.random() * buyProducts.length)];
        speech.text = finalresult;
        window.open('http://localhost/epidx/shop/product/face-mask-pollution-mask-surgical-50-pcs/');
    }

    if(message.includes('thermometer gun')){
        let finalresult = buyProducts[Math.floor(Math.random() * buyProducts.length)];
        speech.text = finalresult;
        window.open('http://localhost/epidx/shop/product/temperature-forehead-ir-thermometer-gun/');
    }

    if(message.includes('sanitizer')){
        let finalresult = buyProducts[Math.floor(Math.random() * buyProducts.length)];
        speech.text = finalresult;
        window.open('http://localhost/epidx/shop/product/shopmore-sanitizer-50ml-with-moisturizers-vitamin-e/');
    }
    



    if(message.includes('thank')){
        let finalresult = thank[Math.floor(Math.random() * thank.length)];
        speech.text = finalresult;
    }
    
    if(message.includes('see you')){
        let finalresult = closing[Math.floor(Math.random() * closing.length)];
        speech.text = finalresult;
    }
    
    window.speechSynthesis.speak(speech);
    chatareamain.appendChild(showchatbotmsg(speech.text));
}



recognition.onresult=function(e){
    let resultIndex = e.resultIndex;
    let transcript = e.results[resultIndex][0].transcript;
    chatareamain.appendChild(showusermsg(transcript));
    chatbotvoice(transcript);
    console.log(transcript);
}
recognition.onend=function(){
    mic.style.background="#ff3b3b";
}
mic.addEventListener("click", function(){
    mic.style.background='#0274BD';
    recognition.start();
    console.log("Activated");
})
