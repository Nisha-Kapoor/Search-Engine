/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
package irassignment;

import java.util.regex.Matcher;
import java.util.regex.Pattern;
import java.io.File;
import java.io.FileInputStream;
import java.io.IOException;
import java.io.PrintWriter;
import org.apache.tika.exception.TikaException;
import org.apache.tika.metadata.Metadata;
import org.apache.tika.parser.ParseContext;
import org.apache.tika.parser.html.HtmlParser;
import org.apache.tika.sax.BodyContentHandler;
import org.xml.sax.SAXException;
public class IRAssignment{
    public static void main(final String[] args) throws IOException,SAXException, TikaException {
        //detecting the file type
        BodyContentHandler handler = new BodyContentHandler(-1);
        Metadata metadata = new Metadata();
        File dir = new File("NYTimesDownloadData");
        File[] directoryListing = dir.listFiles();
        if (directoryListing != null) {
            for (File child : directoryListing) {
                FileInputStream inputstream = new FileInputStream(child);
                ParseContext pcontext = new ParseContext();      
                //Html parser 
                HtmlParser htmlparser = new HtmlParser();
                htmlparser.parse(inputstream, handler, metadata,pcontext);
                String h=handler.toString().replaceAll("[^A-Za-z]+"," ");
                PrintWriter pw= new PrintWriter(new File("big.txt"));
                pw.write(h);
                pw.close();
            }
        }       
    }   
}
 