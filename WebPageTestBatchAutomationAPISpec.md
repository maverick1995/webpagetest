WPT batch automation API spec

We plan to provide the following Python APIs to the WebPageTest (WPT) users to help them automate their batch testing job processing. No  tice your batch tests will be defined as a set of test ids once they are submitted.

def ImportUrls(url\_filename):
> """Load the URLS in the file into memory.

> Args:
> > url\_filename: the file name of the list of interested URLs


> Returns:
> > The list of URLS

> """

def SubmitBatch(url\_list, test\_params, server\_url):
> """Submit the tests to WebPageTest server.

> Args:
> > url\_list: the list of interested URLs
> > test\_params: the user-configured test parameters
> > server\_url: the URL of the WebPageTest server


> Returns:
> > A dictionary which maps a WPT test id to its URL if submission
> > is successful.

> """

def CheckBatchStatus(test\_ids, server\_url):
> """Check the status of tests.

> Args:
> > test\_ids: the list of interested test ids
> > server\_url: the URL of the WebPageTest server


> Returns:
> > A dictionary where key is the test id and content is its status.

> """


def GetXMLResult(test\_ids, server\_url):
> """Obtain the test result in XML format.

> Args:
> > test\_ids: the list of interested test ids
> > server\_url: the URL of WebPageTest server


> Returns:
> > A dictionary where the key is test id and the value is a DOM object of the
> > test result.

> """